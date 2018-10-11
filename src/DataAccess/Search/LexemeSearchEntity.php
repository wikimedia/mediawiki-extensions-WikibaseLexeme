<?php
namespace Wikibase\Lexeme\DataAccess\Search;

use CirrusSearch\CirrusDebugOptions;
use CirrusSearch\Search\ResultsType;
use CirrusSearch\Search\SearchContext;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\DisMax;
use Elastica\Query\Match;
use Elastica\Query\MatchNone;
use Elastica\Query\Term;
use Language;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lexeme\MediaWiki\Content\LexemeContent;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;
use Wikibase\Repo\Search\Elastic\EntitySearchUtils;
use Wikibase\Repo\Search\Elastic\WikibasePrefixSearcher;

/**
 * Implementation of ElasticSearch prefix/completion search for Lexemes
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class LexemeSearchEntity implements EntitySearchHelper {
	const CONTEXT_LEXEME_PREFIX = 'lexeme_prefix';

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;
	/**
	 * Web request context.
	 * Used for implementing debug features such as cirrusDumpQuery.
	 * @var \WebRequest
	 */
	private $request;
	/**
	 * @var Language
	 */
	protected $userLanguage;
	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	protected $lookupFactory;

	/**
	 * @var CirrusDebugOptions|null
	 */
	private $debugOptions;

	public function __construct(
		EntityIdParser $idParser,
		\WebRequest $request,
		Language $userLanguage,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		PrefetchingTermLookup $termLookup,
		CirrusDebugOptions $options = null
	) {
		$this->idParser = $idParser;
		$this->request = $request;
		$this->userLanguage = $userLanguage;
		$this->lookupFactory = new LanguageFallbackLabelDescriptionLookupFactory(
			$languageFallbackChainFactory, $termLookup, $termLookup );
		$this->debugOptions = $options ?? CirrusDebugOptions::fromRequest( $this->request );
	}

	/**
	 * Produce ES query that matches the arguments.
	 *
	 * @param string $text
	 * @param string $entityType
	 * @param SearchContext $context
	 *
	 * @return AbstractQuery
	 */
	protected function getElasticSearchQuery(
		$text,
		$entityType,
		SearchContext $context
	) {
		$context->setOriginalSearchTerm( $text );
		if ( $entityType !== 'lexeme' ) {
			$context->setResultsPossible( false );
			$context->addWarning( 'wikibase-search-bad-entity-type', $entityType );
			return new MatchNone();
		}
		// Drop only leading spaces for exact matches, and all spaces for the rest
		$textExact = ltrim( $text );
		$text = trim( $text );

		$labelsFilter = new Match( 'labels_all.prefix', $text );

		$profile = $context->getConfig()
			->getProfileService()
			->loadProfile( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
				self::CONTEXT_LEXEME_PREFIX );

		$dismax = new DisMax();
		$dismax->setTieBreaker( 0 );

		$fields = [
			[ "lemma.near_match", $profile['exact'] ],
			[ "lemma.near_match_folded", $profile['folded'] ],
			[
				"lexeme_forms.representation.near_match",
				$profile['exact'] * $profile['form-discount'],
			],
			[
				"lexeme_forms.representation.near_match_folded",
				$profile['folded'] * $profile['form-discount'],
			],
		];
		// Fields to which query applies exactly as stated, without trailing space trimming
		$fieldsExact = [];
		if ( $textExact !== $text ) {
			$fields[] =
				[
					"lemma.prefix",
					$profile['prefix'] * $profile['space-discount'],
				];
			$fields[] =
				[
					"lexeme_forms.representation.prefix",
					$profile['prefix'] * $profile['space-discount'] * $profile['form-discount'],
				];
			$fieldsExact[] = [ "lemma.prefix", $profile['prefix'] ];
			$fieldsExact[] =
				[
					"lexeme_forms.representation.prefix",
					$profile['prefix'] * $profile['form-discount'],
				];
		} else {
			$fields[] = [ "lemma.prefix", $profile['prefix'] ];
			$fields[] =
				[
					"lexeme_forms.representation.prefix",
					$profile['prefix'] * $profile['form-discount'],
				];
		}

		foreach ( $fields as $field ) {
			$dismax->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1], $text ) );
		}

		foreach ( $fieldsExact as $field ) {
			$dismax->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1], $textExact ) );
		}

		$labelsQuery = new BoolQuery();
		$labelsQuery->addFilter( $labelsFilter );
		$labelsQuery->addShould( $dismax );
		$titleMatch = new Term( [
				'title.keyword' => EntitySearchUtils::normalizeId( $text, $this->idParser ),
			] );

		$query = new BoolQuery();
		// Match either labels or exact match to title
		$query->addShould( $labelsQuery );
		$query->addShould( $titleMatch );
		$query->setMinimumShouldMatch( 1 );

		// Filter to fetch only given entity type
		$query->addFilter( new Term( [ 'content_model' => LexemeContent::CONTENT_MODEL_ID ] ) );

		return $query;
	}

	/**
	 * Get results type object for this search.
	 * @return ResultsType
	 */
	protected function makeResultType() {
		return new LexemeTermResult(
			$this->idParser,
			$this->userLanguage,
			$this->lookupFactory
		);
	}

	/**
	 * Get entities matching the search term.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage
	) {
		$searcher = new WikibasePrefixSearcher( 0, $limit, $this->debugOptions );
		$query = $this->getElasticSearchQuery( $text, $entityType, $searcher->getSearchContext() );

		$searcher->setResultsType( $this->makeResultType() );

		$searcher->getSearchContext()->setProfileContext( self::CONTEXT_LEXEME_PREFIX );
		$result = $searcher->performSearch( $query );

		// TODO: this is a hack, we need to return Status upstream instead
		foreach ( $result->getErrors() as $error ) {
			wfLogWarning( json_encode( $error ) );
		}

		if ( $result->isOK() ) {
			$result = $result->getValue();
		} else {
			$result = [];
		}

		if ( $searcher->isReturnRaw() ) {
			$result = $searcher->processRawReturn( $result, $this->request );
		}

		return $result;
	}

}

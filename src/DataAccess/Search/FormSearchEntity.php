<?php
namespace Wikibase\Lexeme\DataAccess\Search;

use CirrusSearch\Search\ResultsType;
use CirrusSearch\Search\SearchContext;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\DisMax;
use Elastica\Query\Match;
use Elastica\Query\MatchNone;
use Elastica\Query\Term;
use Wikibase\Lexeme\MediaWiki\Content\LexemeContent;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;
use Wikibase\Repo\Search\Elastic\EntitySearchUtils;

/**
 * Search for entity of type form.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class FormSearchEntity extends LexemeSearchEntity {
	/**
	 * Search limit.
	 * @var int
	 */
	private $limit;

	/**
	 * Produce ES query that matches the arguments.
	 * This is search for forms - matches only form representations
	 * but not lexemes.
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
		// TODO consider using Form::ENTITY_TYPE
		if ( $entityType !== 'form' ) {
			$context->setResultsPossible( false );
			$context->addWarning( 'wikibase-search-bad-entity-type', $entityType );
			return new MatchNone();
		}
		// Drop only leading spaces for exact matches, and all spaces for the rest
		$textExact = ltrim( $text );
		$text = trim( $text );

		$labelsFilter = new Match( 'lexeme_forms.representation.prefix', $text );

		$profile = $context->getConfig()
			->getProfileService()
			->loadProfile( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
				self::CONTEXT_LEXEME_PREFIX );

		$dismax = new DisMax();
		$dismax->setTieBreaker( 0 );

		$fields = [
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
					"lexeme_forms.representation.prefix",
					$profile['prefix'] * $profile['space-discount'] * $profile['form-discount'],
				];
			$fieldsExact[] =
				[
					"lexeme_forms.representation.prefix",
					$profile['prefix'] * $profile['form-discount'],
				];
		} else {
			$fields[] =
				[
					"lexeme_forms.representation.prefix",
					$profile['prefix'] * $profile['form-discount'],
				];
		}

		foreach ( $fields as $field ) {
			$dismax->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1],
				$text ) );
		}

		foreach ( $fieldsExact as $field ) {
			$dismax->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1],
				$textExact ) );
		}

		$labelsQuery = new BoolQuery();
		$labelsQuery->addFilter( $labelsFilter );
		$labelsQuery->addShould( $dismax );
		// lexeme_forms.id is a lowercase_keyword so use Match to apply the analyzer
		$titleMatch = new Match( 'lexeme_forms.id',
			EntitySearchUtils::normalizeId( $text, $this->idParser ) );

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
		return new FormTermResult(
			$this->idParser,
			$this->userLanguage,
			$this->lookupFactory,
			$this->limit
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
		// We need to keep the limit since one document can produce several matches.
		$this->limit = $limit;
		return parent::getRankedSearchResults( $text, $languageCode, $entityType, $limit,
			$strictLanguage );
	}

}

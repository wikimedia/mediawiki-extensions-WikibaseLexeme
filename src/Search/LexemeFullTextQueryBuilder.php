<?php

namespace Wikibase\Lexeme\Search;

use CirrusSearch\Query\FullTextQueryBuilder;
use CirrusSearch\Search\SearchContext;
use Elastica\Query\BoolQuery;
use Elastica\Query\DisMax;
use Elastica\Query\Match;
use Elastica\Query\Term;
use Language;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\Search\Elastic\EntitySearchUtils;
use Wikibase\Repo\WikibaseRepo;

/**
 * Builder for Lexeme fulltext queries
 */
class LexemeFullTextQueryBuilder implements FullTextQueryBuilder {
	/**
	 * Default profile name for lexemes
	 */
	const LEXEME_DEFAULT_PROFILE = 'lexeme_fulltext';
	/**
	 * Lexeme fulltext search context name
	 */
	const CONTEXT_LEXEME_FULLTEXT = 'wikibase_lexeme_fulltext';

	/**
	 * @var array
	 */
	private $settings;
	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;
	/**
	 * @var string User language code
	 */
	private $userLanguage;
	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $lookupFactory;

	/**
	 * @param array $settings Settings from EntitySearchProfiles.php
	 * @param LanguageFallbackLabelDescriptionLookupFactory $lookupFactory
	 * @param EntityIdParser $entityIdParser
	 * @param Language $userLanguage User's display language
	 */
	public function __construct(
		array $settings,
		LanguageFallbackLabelDescriptionLookupFactory $lookupFactory,
		EntityIdParser $entityIdParser,
		Language $userLanguage
	) {
		$this->settings = $settings;
		$this->entityIdParser = $entityIdParser;
		$this->userLanguage = $userLanguage;
		$this->lookupFactory = $lookupFactory;
	}

	/**
	 * Create fulltext builder from global environment.
	 * @param array $settings Configuration from config file
	 * @return LexemeFullTextQueryBuilder
	 * @throws \MWException
	 */
	public static function newFromGlobals( array $settings ) {
		$repo = WikibaseRepo::getDefaultInstance();
		return new static(
			$settings,
			new LanguageFallbackLabelDescriptionLookupFactory(
				$repo->getLanguageFallbackChainFactory(),
				$repo->getPrefetchingTermLookup(),
				$repo->getPrefetchingTermLookup() ),
			$repo->getEntityIdParser(),
			$repo->getUserLanguage()
		);
	}

	/**
	 * Search articles with provided term.
	 *
	 * @param SearchContext $searchContext
	 * @param string $term term to search
	 * @throws \MWException
	 */
	public function build( SearchContext $searchContext, $term ) {
		if ( $searchContext->areResultsPossible() && !$searchContext->isSpecialKeywordUsed() ) {
			// We use entity search query if we did not find any advanced syntax
			// and the base builder did not reject the query
			$this->buildEntitySearchQuery( $searchContext, $term );
		}
		// if we did find advanced query, we keep the old setup but change the result type
		// FIXME: make it dispatch by content model
		$searchContext->setResultsType( new LexemeFulltextResult( $this->entityIdParser,
			$this->userLanguage,
			$this->lookupFactory ) );
	}

	/**
	 * @param SearchContext $searchContext
	 * @return bool
	 */
	public function buildDegraded( SearchContext $searchContext ) {
		// Not doing anything for now
		return false;
	}

	/**
	 * Build a fulltext query for Wikibase entity.
	 * @param SearchContext $searchContext
	 * @param string $term Search term
	 */
	protected function buildEntitySearchQuery( SearchContext $searchContext, $term ) {
		$searchContext->setProfileContext( self::CONTEXT_LEXEME_FULLTEXT );
		$searchContext->addSyntaxUsed( 'lexeme_full_text', 10 );
		/*
		 * Overall query structure is as follows:
		 * - Bool with:
		 *   Filter of namespace = N
		 *   OR (Should with 1 mininmum) of:
		 *     title.keyword = QUERY
		 *     lexeme_forms.id = QUERY
		 *     fulltext match query
		 *
		 * Fulltext match query is:
		 *   Filter of:
		 *      at least one of: all, all.plain matching
		 *   OR (should with 0 minimum) of:
		 *     DISMAX query of: {lemma|form}.near_match
		 *     OR (should with 0 minimum) of:
		 *        all
		 *        all.plain
		 */

		$profile = $this->settings;
		// $fields is collecting all the fields for dismax query to be used in
		// scoring match
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

		$titleMatch = new Term( [
			'title.keyword' => EntitySearchUtils::normalizeId( $term, $this->entityIdParser ),
		] );
		// lexeme_forms.id is a lowercase_keyword so use Match to apply the analyzer
		$formIdMatch = new Match( 'lexeme_forms.id',
			EntitySearchUtils::normalizeId( $term, $this->entityIdParser ) );

		// Main query filter
		$filterQuery = $this->buildSimpleAllFilter( $term );

		// Near match ones, they use constant score
		$nearMatchQuery = new DisMax();
		$nearMatchQuery->setTieBreaker( 0 );
		foreach ( $fields as $field ) {
			$nearMatchQuery->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1],
				$term ) );
		}

		// Tokenized ones
		$tokenizedQuery = $this->buildSimpleAllFilter( $term, 'OR', $profile['any'] );

		// Main labels/desc query
		$fullTextQuery = new BoolQuery();
		$fullTextQuery->addFilter( $filterQuery );
		$fullTextQuery->addShould( $nearMatchQuery );
		$fullTextQuery->addShould( $tokenizedQuery );

		// Main query
		$query = new BoolQuery();
		$query->setParam( 'disable_coord', true );

		// Match either labels or exact match to title
		$query->addShould( $titleMatch );
		$query->addShould( $formIdMatch );
		$query->addShould( $fullTextQuery );
		$query->setMinimumShouldMatch( 1 );

		$searchContext->setMainQuery( $query );
	}

	/**
	 * Builds a simple filter on all and all.plain when all terms must match
	 *
	 * @param string $query
	 * @param string $operator
	 * @param null $boost
	 * @return BoolQuery
	 */
	private function buildSimpleAllFilter( $query, $operator = 'AND', $boost = null ) {
		$filter = new BoolQuery();
		// FIXME: We can't use solely the stem field here
		// - Depending on languages it may lack stopwords,
		// A dedicated field used for filtering would be nice
		foreach ( [ 'all', 'all.plain' ] as $field ) {
			$m = new Match();
			$m->setFieldQuery( $field, $query );
			$m->setFieldOperator( $field, $operator );
			if ( $boost ) {
				$m->setFieldBoost( $field, $boost );
			}
			$filter->addShould( $m );
		}
		return $filter;
	}

}

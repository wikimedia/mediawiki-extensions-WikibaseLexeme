<?php
namespace Wikibase\Lexeme\Search;

use CirrusSearch\Search\ResultsType;
use CirrusSearch\Search\SearchContext;
use Elastica\ResultSet;
use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\Search\Elastic\EntitySearchUtils;

/**
 * This result type implements the result for searching a Wikibase Lexeme.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class LexemeTermResult implements ResultsType {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * Display language
	 * @var Language
	 */
	private $displayLanguage;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $termLookupFactory;

	/**
	 * @param EntityIdParser $idParser
	 * @param Language $displayLanguage User display language
	 * @param LanguageFallbackLabelDescriptionLookupFactory $termLookupFactory
	 *        Lookup factory for assembling descriptions
	 */
	public function __construct(
		EntityIdParser $idParser,
		Language $displayLanguage,
		LanguageFallbackLabelDescriptionLookupFactory $termLookupFactory
	) {
		$this->idParser = $idParser;
		$this->termLookupFactory = $termLookupFactory;
		$this->displayLanguage = $displayLanguage;
	}

	/**
	 * Get the source filtering to be used loading the result.
	 *
	 * @return string[]
	 */
	public function getSourceFiltering() {
		return [
				'namespace',
				'title',
				LemmaField::NAME,
				LexemeLanguageField::NAME,
				LexemeCategoryField::NAME,
		];
	}

	/**
	 * Get the fields to load.  Most of the time we'll use source filtering instead but
	 * some fields aren't part of the source.
	 *
	 * @return string[]
	 */
	public function getFields() {
		return [];
	}

	/**
	 * ES5 variant of getFields.
	 * @return string[]
	 */
	public function getStoredFields() {
		return [];
	}

	/**
	 * Get the highlighting configuration.
	 *
	 * @param array $highlightSource configuration for how to highlight the source.
	 *  Empty if source should be ignored.
	 * @return array|null highlighting configuration for elasticsearch
	 */
	public function getHighlightingConfiguration( array $highlightSource ) {
		$config = [
			'pre_tags' => [ '' ],
			'post_tags' => [ '' ],
			'fields' => [],
		];
		$config['fields']['title'] = [
			'type' => 'experimental',
			'fragmenter' => "none",
			'number_of_fragments' => 0,
			'matched_fields' => [ 'title.keyword' ]
		];
		$config['fields']["lemma"] = [
			'type' => 'experimental',
			'fragmenter' => "none",
			'number_of_fragments' => 0,
			'options' => [
				'skip_if_last_matched' => true,
			],
		];
		$config['fields']["lexeme_forms.representation"] = [
			'type' => 'experimental',
			'fragmenter' => "none",
			'number_of_fragments' => 0,
			'options' => [
				'skip_if_last_matched' => true,
			],
		];

		return $config;
	}

	/**
	 * Get label or return empty string.
	 * @param LabelDescriptionLookup $lookup
	 * @param EntityId $id
	 * @param string $default Default value if unable to retrieve label
	 * @return string Label or "" if does not exist.
	 */
	private function getLabelOrNothing(
		LabelDescriptionLookup $lookup,
		EntityId $id = null,
		$default = ""
	) {
		if ( !$id ) {
			return $default;
		}
		$label = $lookup->getLabel( $id );
		if ( !$label ) {
			return $default;
		}
		return $label->getText();
	}

	/**
	 * Create short lexeme description, e.g.: "German noun" or "English verb"
	 * @param LabelDescriptionLookup $termLookup Lookup service to find labels for lemma parts
	 * @param string $language Language ID, as string
	 * @param string $category Grammatical category ID, as string
	 * @return string
	 * @throws \MWException
	 */
	private function createDescription( LabelDescriptionLookup $termLookup, $language, $category ) {
		$languageId = EntitySearchUtils::parseOrNull( $language, $this->idParser );
		$categoryId = EntitySearchUtils::parseOrNull( $category, $this->idParser );
		return wfMessage( 'wikibaselexeme-description' )
			->inLanguage( $this->displayLanguage )
			->params(
				$this->getLabelOrNothing( $termLookup, $languageId,
					wfMessage( 'wikibaselexeme-unknown-language' )
						->inLanguage( $this->displayLanguage )
						->text() ),
				$this->getLabelOrNothing( $termLookup, $categoryId,
					wfMessage( 'wikibaselexeme-unknown-category' )
						->inLanguage( $this->displayLanguage )
						->text() )
			)->text();
	}

	/**
	 * Convert search result from ElasticSearch result set to TermSearchResult.
	 * @param SearchContext $context
	 * @param ResultSet $result
	 * @return TermSearchResult[] Set of search results, the types of which vary by implementation.
	 */
	public function transformElasticsearchResult( SearchContext $context, ResultSet $result ) {
		$rawResults = $entityIds = [];
		foreach ( $result->getResults() as $r ) {
			$sourceData = $r->getSource();
			$entityId = EntitySearchUtils::parseOrNull( $sourceData['title'], $this->idParser );
			if ( !$entityId ) {
				// Can not parse entity ID - skip it
				continue;
			}

			if ( empty( $sourceData['lexeme_language']['code'] ) ) {
				// FIXME: what we do here? Not sure we want to index all lemma languages
				// Should we just fake the term language code?
				continue;
			}
			$lemmaCode = $sourceData['lexeme_language']['code'];
			// Highlight part contains information about what has actually been matched.
			$highlight = $r->getHighlights();

			if ( !empty( $highlight['title'] ) ) {
				// If we matched title, this means it's a match by ID
				$matchedTermType = 'entityId';
				$matchedTerm = new Term( 'qid', $sourceData['title'] );
			} elseif ( empty( $highlight['lemma'] ) && empty( $highlight['lexeme_forms.representation'] ) ) {
				// Something went wrong, we don't have any highlighting data
				continue;
			} elseif ( !empty( $highlight['lemma'] ) ) {
				// We matched lemma
				$matchedTermType = 'label';
				$matchedTerm = new Term( $lemmaCode, $highlight['lemma'][0] );
			} else {
				// matched one of the forms
				$matchedTermType = 'alias';
				$matchedTerm = new Term( $lemmaCode, $highlight['lexeme_forms.representation'][0] );
			}

			$lang = $sourceData['lexeme_language']['entity'];
			$category = $sourceData['lexical_category'];

			$entityIds[$lang] = EntitySearchUtils::parseOrNull( $lang, $this->idParser );
			$entityIds[$category] = EntitySearchUtils::parseOrNull( $category, $this->idParser );

			// Doing two-stage resolution here since we want to prefetch all labels for
			// auxiliary entities before using them to construct descriptions.
			$rawResults[$entityId->getSerialization()] = [
				'id' => $entityId,
				// TODO: this assumes we always take the first lemma. Maybe we should use
				// the shortest language code or something. That would require us to index
				// lemma language codes though.
				'lemma' => $sourceData['lemma'][0],
				'term' => $matchedTerm,
				'type' => $matchedTermType,
				'lang' => $lang,
				'langcode' => $lemmaCode,
				'category' => $category
			];
		}

		$langCode = $this->displayLanguage->getCode();
		if ( $entityIds ) {
			// Create prefetched lookup
			$termLookup = $this->termLookupFactory->newLabelDescriptionLookup( $this->displayLanguage,
				array_filter( $entityIds ) );
			// Create full descriptons and instantiate TermSearchResult objects
			return array_map( function ( $raw ) use ( $termLookup, $langCode ) {
				return new TermSearchResult(
					$raw['term'],
					$raw['type'],
					$raw['id'],
					new Term( $raw['langcode'], $raw['lemma'] ),
					// We are lying somewhat here, as description might be from fallback languages,
					// but I am not sure there's any better way here.
					new Term( $langCode, $this->createDescription( $termLookup, $raw['lang'], $raw['category'] ) )
				);
			}, $rawResults );
		} else {
			return [];
		}
	}

	/**
	 * @return TermSearchResult[] Empty set of search results
	 */
	public function createEmptyResult() {
		return [];
	}

}

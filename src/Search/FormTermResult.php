<?php
namespace Wikibase\Lexeme\Search;

use CirrusSearch\Search\ResultsType;
use CirrusSearch\Search\SearchContext;
use Elastica\ResultSet;
use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\Search\Elastic\EntitySearchUtils;

/**
 * This result type implements the result for searching a Wikibase Form.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class FormTermResult implements ResultsType {

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
	 * Cache for Lexeme descriptions
	 * @var string[]
	 */
	private $lexemeDescriptions = [];
	/**
	 * Limit how many results to produce
	 * @var int
	 */
	private $limit;

	/**
	 * @param EntityIdParser $idParser
	 * @param Language $displayLanguage User display language
	 * @param LanguageFallbackLabelDescriptionLookupFactory $termLookupFactory
	 *        Lookup factory for assembling descriptions
	 * @param int $limit How many results to produce
	 */
	public function __construct(
		EntityIdParser $idParser,
		Language $displayLanguage,
		LanguageFallbackLabelDescriptionLookupFactory $termLookupFactory,
		$limit
	) {
		$this->idParser = $idParser;
		$this->termLookupFactory = $termLookupFactory;
		$this->displayLanguage = $displayLanguage;
		$this->limit = $limit;
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
				FormsField::NAME
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
		$config['fields']['lexeme_forms.id'] = [
			'type' => 'experimental',
			'fragmenter' => "none",
			'number_of_fragments' => 0,
		];
		$config['fields']["lexeme_forms.representation"] = [
			'type' => 'experimental',
			'fragmenter' => "none",
			'number_of_fragments' => 30,
			'fragment_size' => 1000, // Hopefully this is enough
			'matched_fields' => [ 'lexeme_forms.representation.prefix' ],
			'options' => [
				'skip_if_last_matched' => true,
			],
		];

		return $config;
	}

	/**
	 * Get lexeme description from cache or create it.
	 * @param EntityId $lexemeId
	 * @param LexemeDescription $descriptionMaker
	 * @param string $language Language object for lemma
	 * @param string $category Grammatical category for lemma
	 * @return string Lexeme description string
	 * @throws \MWException
	 */
	private function getLexemeDescription(
		EntityId $lexemeId,
		LexemeDescription $descriptionMaker,
		$language,
		$category
	) {
		$id = $lexemeId->getSerialization();
		if ( !array_key_exists( $id, $this->lexemeDescriptions ) ) {
			$this->lexemeDescriptions[$id] = $descriptionMaker->createDescription( $lexemeId,
				$language, $category );
		}
		return $this->lexemeDescriptions[$id];
	}

	/**
	 * Create Form descriptions, along the lines of:
	 * singular genitive for Leiter (L1): German noun
	 *
	 * @param LexemeDescription $descriptionMaker Description maker, to look up labels
	 * @param EntityId[] $features Feature IDs list
	 * @param EntityId $lexemeId Main lexeme
	 * @param string $lemma Lexeme's lemma
	 * @param string $description Lexeme description
	 * @return string
	 * @throws \MWException
	 */
	private function createDescription(
		LexemeDescription $descriptionMaker,
		$features,
		EntityId $lexemeId,
		$lemma,
		$description
	) {
		// Create list of feature labels, separated by space
		// TODO: do we need to i18n this or space-separated list is good enough?
		$featuresString = implode( ' ', array_filter( array_map(
			function ( EntityId $featureId ) use ( $descriptionMaker ) {
				// TODO: do we need separate string for this?
				return $descriptionMaker->getLabelOrDefault( $featureId,
					wfMessage( 'wikibaselexeme-unknown-category' )
						->inLanguage( $this->displayLanguage ) );
			}, $features ) ) );
		if ( empty( $featuresString ) ) {
			$featuresString = wfMessage( 'wikibaselexeme-no-features' )
				->inLanguage( $this->displayLanguage );
		}
		return wfMessage( 'wikibaselexeme-form-description' )
			->inLanguage( $this->displayLanguage )
			->params(
				$featuresString,
				$lemma,
				$lexemeId->getSerialization(),
				$description
			)->text();
	}

	/**
	 * Produce raw result for ID-type match.
	 * @param string[][] $highlight Highlighter data
	 * @param array $sourceData Lexeme source data
	 * @return array|null Null if match is bad
	 */
	private function getIdResult( $highlight, $sourceData ) {
		$formId = $highlight['lexeme_forms.id'][0];
		$formIdParsed = EntitySearchUtils::parseOrNull( $formId, $this->idParser );
		if ( !$formIdParsed ) {
			// Got some bad id?? Weird.
			return null;
		}
		$repr = '';
		$features = [];
		foreach ( $sourceData['lexeme_forms'] as $form ) {
			if ( $form['id'] === $formId ) {
				// TODO: how we choose one?
				$repr = $form['representation'][0];
				// Convert features to EntityId's
				$features = array_filter( array_map( function ( $featureId ) {
					return EntitySearchUtils::parseOrNull( $featureId, $this->idParser );
				}, $form['features'] ) );
				break;
			}
		}
		if ( empty( $repr ) ) {
			// Didn't find the right id? Weird, skip it.
			return null;
		}

		return [
			'id' => $formIdParsed,
			'representation' => $repr,
			'features' => $features,
			'term' => new Term( 'qid', $formId ),
			'type' => 'entityId',
		];
	}

	/**
	 * Get data for specific form
	 * @param string[][] $highlight  Highlighter data
	 * @param array $form Form source data
	 * @param string $lemmaCode Language code for main lemma
	 * @return array|null Null if match is bad
	 */
	private function getRepresentationResult( $highlight, $form, $lemmaCode ) {
		$reprMatches = array_intersect( $form['representation'],
			$highlight['lexeme_forms.representation'] );
		if ( !$reprMatches ) {
			return null;
		}
		// matches the data
		$formIdParsed = EntitySearchUtils::parseOrNull( $form['id'], $this->idParser );
		if ( !$formIdParsed ) {
			// Got some bad id?? Weird.
			return null;
		}
		// Convert features to EntityId's
		$featureIds = array_filter( array_map( function ( $featureId ) {
			return EntitySearchUtils::parseOrNull( $featureId, $this->idParser );
		}, $form['features'] ) );
		return [
			'id' => $formIdParsed,
			// TODO: how we choose the best one of many?
			'representation' => reset( $form['representation'] ),
			'features' => $featureIds,
			// TODO: This may not be true, since matched representation can be
			// from another language...Not sure what to do about it.
			'term' => new Term( $lemmaCode, reset( $reprMatches ) ),
			'type' => 'label',
		];
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
				// TODO: what we do here if no language code?
				// Not sure we want to index all lemma languages.
				// Should we just fake the term language code?
				continue;
			}

			$lemmaCode = LexemeTermResult::extractLanguageCode( $sourceData );

			// Highlight part contains information about what has actually been matched.
			$highlight = $r->getHighlights();

			$lang = $sourceData['lexeme_language']['entity'];
			$category = $sourceData['lexical_category'];

			$features = [];
			$lexemeData = [
				'lexemeId' => $entityId,
				'lemma' => $sourceData['lemma'][0],
				'lang' => $lang,
				'langcode' => $lemmaCode,
				'category' => $category
			];
			// Doing two-stage resolution here since we want to prefetch all labels for
			// auxiliary entities before using them to construct descriptions.
			if ( !empty( $highlight['lexeme_forms.id'] ) ) {
				// If we matched for ID, this means it's a match by ID
				$idResult = $this->getIdResult( $highlight, $sourceData );
				if ( !$idResult ) {
					continue;
				}

				$rawResults[$highlight['lexeme_forms.id'][0]] = $idResult + $lexemeData;
				$features = array_merge( $features, $idResult['features'] );
			} elseif ( !empty( $highlight['lexeme_forms.representation'] ) ) {
				// We matched form representation, let's see which ones we've got
				// Find all forms whose representations match what we have found.
				// Note this can be more than one.
				foreach ( $sourceData['lexeme_forms'] as $form ) {
					$formResult = $this->getRepresentationResult( $highlight, $form, $lemmaCode );
					if ( !$formResult ) {
						continue;
					}
					$rawResults[$form['id']] = $formResult + $lexemeData;
					$features = array_merge( $features, $formResult['features'] );
				}
			} else {
				// TODO: No data to match, skip it. Should we report something?
				continue;
			}

			$entityIds[$lang] = EntitySearchUtils::parseOrNull( $lang, $this->idParser );
			$entityIds[$category] = EntitySearchUtils::parseOrNull( $category, $this->idParser );
			foreach ( $features as $feature ) {
				$entityIds[$feature->getSerialization()] = $feature;
			}
		}

		$langCode = $this->displayLanguage->getCode();
		if ( empty( $rawResults ) ) {
			return [];
		}
		// Create prefetched lookup
		$termLookup = $this->termLookupFactory->newLabelDescriptionLookup( $this->displayLanguage,
			array_filter( $entityIds ) );
		$descriptionMaker = new LexemeDescription( $termLookup, $this->idParser,
			$this->displayLanguage );
		// Create full descriptions and instantiate TermSearchResult objects
		return array_map(
			function ( $raw ) use ( $descriptionMaker, $langCode ) {
				return $this->produceTermResult( $descriptionMaker, $langCode, $raw );
			},
			array_slice( $rawResults, 0, $this->limit )
		);
	}

	/**
	 * Produce TermSearchResult from raw result data.
	 * @param LexemeDescription $descriptionMaker
	 * @param string $langCode
	 * @param array $raw
	 * @return TermSearchResult
	 * @throws \MWException
	 */
	private function produceTermResult(
		LexemeDescription $descriptionMaker,
		$langCode,
		array $raw
	) {
		return new TermSearchResult(
			$raw['term'],
			$raw['type'],
			$raw['id'],
			// We are lying somewhat here, as description might be from fallback languages,
			// but I am not sure there's any better way here.
			new Term( $raw['langcode'], $raw['representation'] ),
			new Term( $langCode,
				$this->createDescription(
					$descriptionMaker, $raw['features'], $raw['lexemeId'], $raw['lemma'],
					$this->getLexemeDescription( $raw['lexemeId'], $descriptionMaker,
						$raw['lang'], $raw['category'] )
				) )
		);
	}

	/**
	 * @return TermSearchResult[] Empty set of search results
	 */
	public function createEmptyResult() {
		return [];
	}

}

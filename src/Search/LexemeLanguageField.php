<?php
namespace Wikibase\Lexeme\Search;

use CirrusSearch;
use DataValues\StringValue;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lexeme\DataModel\Lexeme;

/**
 * Lexeme language field - this contains Q-id of lexeme language.
 */
class LexemeLanguageField extends LexemeKeywordField {
	const NAME = 'lexeme_language';

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyId|null
	 */
	private $lexemeLanguageCodePropertyId;

	public function __construct(
		EntityLookup $entityLookup,
		PropertyId $lexemeLanguageCodePropertyId = null
	) {
		parent::__construct();
		$this->lexemeLanguageCodePropertyId = $lexemeLanguageCodePropertyId;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * Create mapping for Lexeme language.
	 * Two fields:
	 * - entity - Q-id of language entity
	 * - code - language code for the language
	 * @param SearchEngine $engine
	 *
	 * @return array
	 */
	public function getMapping( SearchEngine $engine ) {
		// Since we need a specially tuned field, we can not use
		// standard search engine types.
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}
		return [
			'type' => 'object',
			'properties' => [
				// Both subfields are keywords
				'entity' => parent::getMapping( $engine ),
				'code' => parent::getMapping( $engine ),
			],
		];
	}

	/**
	 * Extract language code from language entity
	 * @param EntityId $languageId The ID of the language entity
	 * @return null|string
	 */
	private function getLanguageCode( EntityId $languageId ) {
		if ( !$this->lexemeLanguageCodePropertyId ) {
			return null;
		}
		$langEntity = $this->entityLookup->getEntity( $languageId );
		if ( !$langEntity || !( $langEntity instanceof StatementListProvider ) ) {
			return null;
		}
		$langCodes = $langEntity->getStatements()->getByPropertyId( $this->lexemeLanguageCodePropertyId );
		if ( $langCodes->isEmpty() ) {
			return null;
		}
		// if there are more than one code, take the first one.
		$codeSnak = $langCodes->getAllSnaks()[0];
		if ( !( $codeSnak instanceof PropertyValueSnak ) ) {
			return null;
		}
		$value = $codeSnak->getDataValue();
		if ( !( $value instanceof StringValue ) ) {
			return null;
		}
		return $value->getValue();
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return mixed Get the value of the field to be indexed when a page/document
	 *               is indexed. This might be an array with nested data, if the field
	 *               is defined with nested type or an int or string for simple field types.
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( !( $entity instanceof Lexeme ) ) {
			return [];
		}
		/**
		 * @var Lexeme $entity
		 */
		$language = $entity->getLanguage();
		return [
			'entity' => $language->getSerialization(),
			'code' => $this->getLanguageCode( $language ),
		];
	}

	/**
	 * Set engine hints.
	 * Specifically, sets noop hint so that forms would be compared
	 * as arrays and changes in language parts would be processed correctly.
	 * @param SearchEngine $engine
	 * @return array
	 */
	public function getEngineHints( SearchEngine $engine ) {
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}
		return [ \CirrusSearch\Search\CirrusIndexField::NOOP_HINT => "equals" ];
	}

}

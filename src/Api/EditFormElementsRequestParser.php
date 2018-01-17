<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Api\Error\ApiError;
use Wikibase\Lexeme\Api\Error\FormMustHaveAtLeastOneRepresentation;
use Wikibase\Lexeme\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\Api\Error\JsonFieldIsNotAnItemId;
use Wikibase\Lexeme\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Api\Error\ParameterIsNotFormId;
use Wikibase\Lexeme\Api\Error\ParameterIsRequired;
use Wikibase\Lexeme\Api\Error\RepresentationLanguageCanNotBeEmpty;
use Wikibase\Lexeme\Api\Error\RepresentationsMustHaveUniqueLanguage;
use Wikibase\Lexeme\Api\Error\RepresentationTextCanNotBeEmpty;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @license GPL-2.0+
 */
class EditFormElementsRequestParser {

	const PARAM_DATA = 'data';

	const PARAM_FORM_ID = 'formId';

	/**
	 * @var ItemIdParser
	 */
	private $itemIdParser;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->itemIdParser = new ItemIdParser();
		$this->entityIdParser = $entityIdParser;
	}

	public function parse( array $params ) {
		//TODO: validate language. How?
		//TODO: validate if all grammatical features exist
		$errors = $this->validateRequiredFieldsPresent( $params );
		if ( $errors ) {
			return EditFormElementsRequestParserResult::newWithErrors( $errors );
		}

		$data = json_decode( $params[self::PARAM_DATA] );
		if ( !is_object( $data ) ) {
			return EditFormElementsRequestParserResult::newWithErrors(
				[
					new ParameterIsNotAJsonObject( self::PARAM_DATA, $params[self::PARAM_DATA] )
				]
			);
		}

		$errors = $this->validateDataStructure( $data );
		if ( $errors ) {
			return EditFormElementsRequestParserResult::newWithErrors( $errors );
		}

		$formId = $this->parseFormId( $params['formId'], $errors );
		$representations = $this->parseRepresentations( $data->representations, $errors );
		$grammaticalFeatures = $this->parseGrammaticalFeatures( $data->grammaticalFeatures, $errors );

		if ( $errors ) {
			return EditFormElementsRequestParserResult::newWithErrors( $errors );
		}

		return EditFormElementsRequestParserResult::newWithRequest(
			new EditFormElementsRequest(
				$formId,
				$representations,
				$grammaticalFeatures
			)
		);
	}

	/**
	 * @param $id
	 * @param array &$errors
	 * @return FormId|null
	 */
	private function parseFormId( $id, array &$errors ) {
		try {
			$formId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			$errors[] = new ParameterIsNotFormId( self::PARAM_FORM_ID, $id );
			return null;
		}

		if ( $formId->getEntityType() !== 'form' ) {
			$errors[] = new ParameterIsNotFormId( self::PARAM_FORM_ID, $id );
			return null;
		}

		return $formId;
	}

	/**
	 * @param \stdClass[] $givenRepresentations
	 * @param ApiError[] &$errors
	 * @return TermList
	 */
	private function parseRepresentations( array $givenRepresentations, array &$errors ) {
		if ( empty( $givenRepresentations ) ) {
			$errors[] = new FormMustHaveAtLeastOneRepresentation( self::PARAM_DATA, [ 'representations' ] );
		}

		//FIXME: Array may contain representation with empty text (or untrimmed) which won't be added
		$result = [];

		foreach ( $givenRepresentations as $index => $el ) {
			$incomplete = false;

			if ( !property_exists( $el, 'representation' ) ) {
				$errors[] = new JsonFieldIsRequired(
					self::PARAM_DATA,
					[ 'representations', $index, 'representation' ]
				);
				$incomplete = true;
			} elseif ( empty( $el->representation ) ) {
				$errors[] = new RepresentationTextCanNotBeEmpty(
					self::PARAM_DATA,
					[ 'representations', $index, 'representation' ]
				);
				$incomplete = true;
			}

			if ( !property_exists( $el, 'language' ) ) {
				$errors[] = new JsonFieldIsRequired(
					self::PARAM_DATA,
					[ 'representations', $index, 'language' ]
				);
				$incomplete = true;
			} elseif ( empty( $el->language ) ) {
				$errors[] = new RepresentationLanguageCanNotBeEmpty(
					self::PARAM_DATA,
					[ 'representations', $index, 'language' ]
				);
				$incomplete = true;
			}

			if ( $incomplete ) {
				continue;
			}

			if ( isset( $result[$el->language] ) ) {
				$errors[] = new RepresentationsMustHaveUniqueLanguage(
					self::PARAM_DATA,
					[ 'representations', $index, 'language' ],
					$el->language
				);
			}

			$result[$el->language] = $el->representation;
		}

		$terms = [];
		foreach ( $result as $language => $representation ) {
			$terms[] = new Term( $language, $representation );
		}

		return new TermList( $terms );
	}

	/**
	 * @param string[] $data
	 * @param ApiError[] $errors
	 * @return ItemId[]
	 */
	private function parseGrammaticalFeatures( array $data, array &$errors ) {
		$features = [];

		foreach ( $data as $index => $featureId ) {
			try {
				$id = $this->itemIdParser->parse( $featureId );
			} catch ( EntityIdParsingException $e ) {
				$errors[] = new JsonFieldIsNotAnItemId(
					self::PARAM_DATA,
					[ 'grammaticalFeatures', $index ],
					$featureId
				);
				continue;
			}

			$features[] = $id;
		}

		return $features;
	}

	private function validateDataStructure( $data ) {
		$errors = [];

		if ( !property_exists( $data, 'representations' ) ) {
			$errors[] = new JsonFieldIsRequired( self::PARAM_DATA, [ 'representations' ] );
		} elseif ( !is_array( $data->representations ) ) {
			$errors[] = new JsonFieldHasWrongType(
				self::PARAM_DATA,
				[ 'representations' ],
				'array',
				gettype( $data->representations )
			);
		}

		if ( !property_exists( $data, 'grammaticalFeatures' ) ) {
			$errors[] = new JsonFieldIsRequired( self::PARAM_DATA, [ 'grammaticalFeatures' ] );
		} elseif ( !is_array( $data->grammaticalFeatures ) ) {
			$errors[] = new JsonFieldHasWrongType(
				self::PARAM_DATA,
				[ 'grammaticalFeatures' ],
				'array',
				gettype( $data->grammaticalFeatures )
			);
		}

		return $errors;
	}

	private function validateRequiredFieldsPresent( array $params ) {
		$errors = [];

		if ( !array_key_exists( self::PARAM_FORM_ID, $params ) ) {
			$errors[] = new ParameterIsRequired( self::PARAM_FORM_ID );
		}

		if ( !array_key_exists( self::PARAM_DATA, $params ) ) {
			$errors[] = new ParameterIsRequired( self::PARAM_DATA );
		}

		return $errors;
	}

}

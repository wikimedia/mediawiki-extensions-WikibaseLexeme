<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\Api\Error\ParameterIsNotFormId;
use Wikibase\Lexeme\Api\Error\ParameterIsRequired;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormRequestParser {

	const PARAM_FORM_ID = 'formId';
	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var ItemIdParser
	 */
	private $itemIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
		$this->itemIdParser = new ItemIdParser();
	}

	/**
	 * @param array $params
	 * @return RemoveFormRequestParserResult
	 */
	public function parse( array $params ) {
		$errors = $this->validateRequiredFieldsPresent( $params );
		if ( $errors ) {
			return RemoveFormRequestParserResult::newWithErrors( $errors );
		}

		$formId = $this->parseFormId( $params[self::PARAM_FORM_ID], $errors );

		if ( $errors ) {
			return RemoveFormRequestParserResult::newWithErrors( $errors );
		}

		return RemoveFormRequestParserResult::newWithRequest(
			new RemoveFormRequest( $formId )
		);
	}

	/**
	 * @param string $id
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

	private function validateRequiredFieldsPresent( array $params ) {
		$errors = [];

		if ( !array_key_exists( self::PARAM_FORM_ID, $params ) ) {
			$errors[] = new ParameterIsRequired( self::PARAM_FORM_ID );
		}

		return $errors;
	}

}

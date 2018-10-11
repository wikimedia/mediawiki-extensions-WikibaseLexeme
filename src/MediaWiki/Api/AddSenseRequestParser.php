<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class AddSenseRequestParser {

	const PARAM_DATA = 'data';
	const PARAM_LEXEME_ID = 'lexemeId';
	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EditSenseChangeOpDeserializer
	 */
	private $editSenseChangeOpDeserializer;

	public function __construct(
		EntityIdParser $entityIdParser,
		EditSenseChangeOpDeserializer $editSenseChangeOpDeserializer
	) {
		$this->entityIdParser = $entityIdParser;
		$this->editSenseChangeOpDeserializer = $editSenseChangeOpDeserializer;
	}

	/**
	 * @param array $params
	 * @return AddSenseRequest
	 */
	public function parse( array $params ) {
		// guarded against missing fields by AddSense::getAllowedParams()

		//TODO: validate language. How?

		$dataValidation = ValidationContext::create( self::PARAM_DATA );

		$data = json_decode( $params[self::PARAM_DATA], true );
		if ( !is_array( $data ) || empty( $data ) ) {
			$dataValidation->addViolation(
				new ParameterIsNotAJsonObject( self::PARAM_DATA, $params[self::PARAM_DATA] )
			);
		}

		$lexemeId = $this->parseLexemeId(
			$params[self::PARAM_LEXEME_ID],
			ValidationContext::create( self::PARAM_LEXEME_ID )
		);

		$this->editSenseChangeOpDeserializer->setContext( $dataValidation );

		return new AddSenseRequest(
			$lexemeId,
			$this->editSenseChangeOpDeserializer->createEntityChangeOp( $data )
		);
	}

	/**
	 * @param string $id
	 * @param ValidationContext $validationContext
	 * @return LexemeId|null
	 */
	private function parseLexemeId( $id, ValidationContext $validationContext ) {
		try {
			$lexemeId = $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			$validationContext->addViolation( new ParameterIsNotLexemeId( $id ) );
			return null;
		}

		if ( $lexemeId->getEntityType() !== 'lexeme' ) {
			$validationContext->addViolation( new ParameterIsNotLexemeId( $id ) );
			return null;
		}

		/**
		 * @var $lexemeId LexemeId
		 */

		return $lexemeId;
	}

}

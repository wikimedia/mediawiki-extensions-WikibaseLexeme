<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class AddFormRequestParser {

	const PARAM_DATA = 'data';
	const PARAM_LEXEME_ID = 'lexemeId';
	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EditFormChangeOpDeserializer
	 */
	private $editFormChangeOpDeserializer;

	public function __construct(
		EntityIdParser $entityIdParser,
		EditFormChangeOpDeserializer $editFormChangeOpDeserializer
	) {
		$this->entityIdParser = $entityIdParser;
		$this->editFormChangeOpDeserializer = $editFormChangeOpDeserializer;
	}

	public function parse( array $params ): AddFormRequest {
		// guarded against missing fields by AddForm::getAllowedParams()

		//TODO: validate language. How?
		//TODO: validate if all grammatical features exist

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

		$this->editFormChangeOpDeserializer->setContext( $dataValidation );

		return new AddFormRequest(
			$lexemeId,
			$this->editFormChangeOpDeserializer->createEntityChangeOp( $data )
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

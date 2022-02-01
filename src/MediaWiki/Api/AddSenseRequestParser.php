<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use LogicException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotLexemeId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @license GPL-2.0-or-later
 */
class AddSenseRequestParser {

	public const PARAM_DATA = 'data';
	public const PARAM_LEXEME_ID = 'lexemeId';
	public const PARAM_BASEREVID = 'baserevid';
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
			throw new LogicException( 'ApiUsageException not thrown' );
		}

		$lexemeId = $this->parseLexemeId(
			$params[self::PARAM_LEXEME_ID],
			ValidationContext::create( self::PARAM_LEXEME_ID )
		);

		$baseRevId = null;
		if ( isset( $params[ self::PARAM_BASEREVID ] ) ) {
			$baseRevId = (int)$params[self::PARAM_BASEREVID];
		}

		$this->editSenseChangeOpDeserializer->setContext( $dataValidation );

		return new AddSenseRequest(
			$lexemeId,
			$this->editSenseChangeOpDeserializer->createEntityChangeOp( $data ),
			$baseRevId
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
		 * @var LexemeId $lexemeId
		 */
		'@phan-var LexemeId $lexemeId';

		return $lexemeId;
	}

}

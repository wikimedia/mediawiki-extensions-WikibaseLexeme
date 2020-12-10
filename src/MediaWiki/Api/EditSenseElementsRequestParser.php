<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use LogicException;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditSenseChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @license GPL-2.0-or-later
 */
class EditSenseElementsRequestParser {

	public const PARAM_DATA = 'data';

	public const PARAM_SENSE_ID = 'senseId';
	public const PARAM_BASEREVID = 'baserevid';

	/**
	 * @var SenseIdDeserializer
	 */
	private $senseIdDeserializer;

	/**
	 * @var EditSenseChangeOpDeserializer
	 */
	private $editSenseChangeOpDeserializer;

	public function __construct(
		SenseIdDeserializer $senseIdDeserializer,
		EditSenseChangeOpDeserializer $editSenseChangeOpDeserializer
	) {
		$this->senseIdDeserializer = $senseIdDeserializer;
		$this->editSenseChangeOpDeserializer = $editSenseChangeOpDeserializer;
	}

	/**
	 * @param array $params
	 * @return EditSenseElementsRequest
	 */
	public function parse( array $params ) {
		// guarded against missing fields by EditSenseElements::getAllowedParams()

		//TODO: validate language. How?

		$dataValidation = ValidationContext::create( self::PARAM_DATA );

		$data = json_decode( $params[self::PARAM_DATA], true );
		if ( !is_array( $data ) || empty( $data ) ) {
			$dataValidation->addViolation(
				new ParameterIsNotAJsonObject( self::PARAM_DATA, $params[self::PARAM_DATA] )
			);
			throw new LogicException( 'ApiUsageException not thrown' );
		}

		$senseId = $this->senseIdDeserializer->deserialize(
			$params[self::PARAM_SENSE_ID],
			ValidationContext::create( self::PARAM_SENSE_ID )
		);

		$this->editSenseChangeOpDeserializer->setContext(
			$dataValidation
		);

		$baseRevId = null;
		if ( isset( $params[ self::PARAM_BASEREVID ] ) ) {
			$baseRevId = (int)$params[self::PARAM_BASEREVID];
		}

		return new EditSenseElementsRequest(
			$senseId,
			$this->editSenseChangeOpDeserializer->createEntityChangeOp( $data ),
			$baseRevId
		);
	}

}

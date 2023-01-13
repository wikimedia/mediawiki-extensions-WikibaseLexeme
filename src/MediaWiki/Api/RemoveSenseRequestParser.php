<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @license GPL-2.0-or-later
 */
class RemoveSenseRequestParser {

	public const PARAM_SENSE_ID = 'id';
	public const PARAM_BASEREVID = 'baserevid';

	/**
	 * @var SenseIdDeserializer
	 */
	private $senseIdDeserializer;

	public function __construct( SenseIdDeserializer $senseIdDeserializer ) {
		$this->senseIdDeserializer = $senseIdDeserializer;
	}

	/**
	 * @param array $params
	 * @return RemoveSenseRequest
	 */
	public function parse( array $params ) {
		// missing $params[self::PARAM_SENSE_ID] caught by RemoveSense::getAllowedParams()

		$senseId = $this->senseIdDeserializer->deserialize(
			$params[self::PARAM_SENSE_ID],
			ValidationContext::create( self::PARAM_SENSE_ID )
		);

		$baseRevId = null;
		if ( isset( $params[ self::PARAM_BASEREVID ] ) ) {
			$baseRevId = (int)$params[self::PARAM_BASEREVID];
		}

		return new RemoveSenseRequest( $senseId, $baseRevId );
	}

}

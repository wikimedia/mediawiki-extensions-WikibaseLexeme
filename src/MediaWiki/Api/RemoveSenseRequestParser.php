<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;

/**
 * @license GPL-2.0-or-later
 */
class RemoveSenseRequestParser {

	const PARAM_SENSE_ID = 'id';

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

		return new RemoveSenseRequest( $senseId );
	}

}

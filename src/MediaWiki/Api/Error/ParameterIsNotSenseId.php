<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 */
class ParameterIsNotSenseId implements ApiError {

	/**
	 * @var string
	 */
	private $given;

	/**
	 * @param string $given
	 */
	public function __construct( $given ) {
		$this->given = $given;
	}

	/**
	 * TODO senseId can occur as param (senseId) or in json (data). Clean generic $path solution?
	 * Proposal: Unification of field and path (fields being first part of path no extra treatment)
	 */
	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		$message = new Message(
			'apierror-wikibaselexeme-parameter-not-sense-id',
			[ $parameterName, implode( '/', $path ), json_encode( $this->given ) ]
		);
		return new ApiMessage( $message, 'bad-request' );
	}

}

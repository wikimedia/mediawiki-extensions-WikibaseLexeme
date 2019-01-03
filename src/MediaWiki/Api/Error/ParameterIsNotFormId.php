<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use ApiMessage;
use Message;

/**
 * @license GPL-2.0-or-later
 */
class ParameterIsNotFormId implements ApiError {

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
	 * TODO formId can occur as param (formId) or in json (data). Clean generic $path solution?
	 * Proposal: Unification of field and path (fields being first part of path no extra treatment)
	 *
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new Message(
			'apierror-wikibaselexeme-parameter-not-form-id',
			[ $parameterName, implode( '/', $path ), json_encode( $this->given ) ]
		);
		return new ApiMessage( $message, 'bad-request' );
	}

}

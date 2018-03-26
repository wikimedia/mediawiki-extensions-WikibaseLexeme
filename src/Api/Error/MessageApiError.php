<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class MessageApiError implements ApiError {

	/**
	 * @var \Message
	 */
	private $message;

	/**
	 * @param \Message $message
	 */
	public function __construct( \Message $message ) {
		$this->message = $message;
	}

	/**
	 * @return \ApiMessage
	 */
	public function asApiMessage() {
		return new \ApiMessage( $this->message, 'bad-request' );
	}

}

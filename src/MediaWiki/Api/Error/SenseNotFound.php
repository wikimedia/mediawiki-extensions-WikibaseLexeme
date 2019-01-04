<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use ApiMessage;
use Message;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class SenseNotFound implements ApiError {

	/**
	 * @var SenseId
	 */
	private $senseId;

	public function __construct( SenseId $senseId ) {
		$this->senseId = $senseId;
	}

	/**
	 * @return ApiMessage
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new Message(
			'apierror-wikibaselexeme-sense-not-found',
			[ $parameterName, $this->senseId->serialize() ]
		);
		return new ApiMessage( $message, 'not-found', [
			'parameterName' => $parameterName,
			'fieldPath' => []
		] );
	}

}

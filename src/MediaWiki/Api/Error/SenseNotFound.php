<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;
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

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		$message = new Message(
			'apierror-wikibaselexeme-sense-not-found',
			[ $parameterName, $this->senseId->getSerialization() ]
		);
		return new ApiMessage( $message, 'not-found', [
			'parameterName' => $parameterName,
			'fieldPath' => [],
		] );
	}

}

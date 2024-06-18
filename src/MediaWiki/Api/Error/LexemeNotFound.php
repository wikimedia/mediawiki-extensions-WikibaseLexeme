<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Message\Message;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class LexemeNotFound implements ApiError {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	public function __construct( LexemeId $lexemeId ) {
		$this->lexemeId = $lexemeId;
	}

	/**
	 * @return \ApiMessage
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new Message(
			'apierror-wikibaselexeme-lexeme-not-found',
			[ $parameterName, $this->lexemeId->getSerialization() ]
		);
		return new \ApiMessage( $message, 'not-found', [
			'parameterName' => $parameterName,
			'fieldPath' => [],
		] );
	}

}

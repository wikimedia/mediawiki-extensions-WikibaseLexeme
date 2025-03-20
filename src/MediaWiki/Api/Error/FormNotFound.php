<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 */
class FormNotFound implements ApiError {

	/**
	 * @var FormId
	 */
	private $formId;

	public function __construct( FormId $formId ) {
		$this->formId = $formId;
	}

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		$message = new Message(
			'apierror-wikibaselexeme-form-not-found',
			[ $parameterName, $this->formId->getSerialization() ]
		);
		return new ApiMessage( $message, 'not-found', [
			'parameterName' => $parameterName,
			'fieldPath' => [],
		] );
	}

}

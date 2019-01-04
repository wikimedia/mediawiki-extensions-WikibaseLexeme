<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use ApiMessage;
use Message;
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

	/**
	 * @return ApiMessage
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new Message(
			'apierror-wikibaselexeme-form-not-found',
			[ $parameterName, $this->formId->serialize() ]
		);
		return new ApiMessage( $message, 'not-found', [
			'parameterName' => $parameterName,
			'fieldPath' => []
		] );
	}

}

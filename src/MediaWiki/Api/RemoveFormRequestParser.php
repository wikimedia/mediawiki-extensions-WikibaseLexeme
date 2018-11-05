<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormRequestParser {

	const PARAM_FORM_ID = 'id';

	/**
	 * @var FormIdDeserializer
	 */
	private $formIdDeserializer;

	public function __construct( FormIdDeserializer $formIdDeserializer ) {
		$this->formIdDeserializer = $formIdDeserializer;
	}

	/**
	 * @param array $params
	 * @return RemoveFormRequest
	 */
	public function parse( array $params ) {
		// missing $params[self::PARAM_FORM_ID] caught by RemoveForm::getAllowedParams()

		$formId = $this->formIdDeserializer->deserialize(
			$params[self::PARAM_FORM_ID],
			ValidationContext::create( self::PARAM_FORM_ID )
		);

		return new RemoveFormRequest( $formId );
	}

}

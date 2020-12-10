<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use LogicException;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotAJsonObject;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\EditFormChangeOpDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequestParser {

	public const PARAM_DATA = 'data';

	public const PARAM_FORM_ID = 'formId';

	public const PARAM_BASEREVID = 'baserevid';

	/**
	 * @var FormIdDeserializer
	 */
	private $formIdDeserializer;

	/**
	 * @var EditFormChangeOpDeserializer
	 */
	private $editFormChangeOpDeserializer;

	public function __construct(
		FormIdDeserializer $formIdDeserializer,
		EditFormChangeOpDeserializer $editFormChangeOpDeserializer
	) {
		$this->formIdDeserializer = $formIdDeserializer;
		$this->editFormChangeOpDeserializer = $editFormChangeOpDeserializer;
	}

	/**
	 * @param array $params
	 * @return EditFormElementsRequest
	 */
	public function parse( array $params ) {
		// guarded against missing fields by EditFormElements::getAllowedParams()

		//TODO: validate language. How?
		//TODO: validate if all grammatical features exist

		$dataValidation = ValidationContext::create( self::PARAM_DATA );

		$data = json_decode( $params[self::PARAM_DATA], true );
		if ( !is_array( $data ) || empty( $data ) ) {
			$dataValidation->addViolation(
				new ParameterIsNotAJsonObject( self::PARAM_DATA, $params[self::PARAM_DATA] )
			);
			throw new LogicException( 'ApiUsageException not thrown' );
		}

		$formId = $this->formIdDeserializer->deserialize(
			$params[self::PARAM_FORM_ID],
			ValidationContext::create( self::PARAM_FORM_ID )
		);

		$this->editFormChangeOpDeserializer->setContext(
			$dataValidation
		);

		$baseRevId = null;
		if ( isset( $params[ self::PARAM_BASEREVID ] ) ) {
			$baseRevId = (int)$params[self::PARAM_BASEREVID];
		}

		return new EditFormElementsRequest(
			$formId,
			$this->editFormChangeOpDeserializer->createEntityChangeOp( $data ),
			$baseRevId
		);
	}

}

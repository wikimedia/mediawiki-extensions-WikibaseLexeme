<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormRequest {

	/**
	 * @var FormId
	 */
	private $formId;

	public function __construct( FormId $formId ) {
		$this->formId = $formId;
	}

	/**
	 * @return ChangeOpRemoveForm
	 */
	public function getChangeOp() {
		return new ChangeOpRemoveForm( $this->formId );
	}

	/**
	 * @return FormId
	 */
	public function getFormId() {
		return $this->formId;
	}

}

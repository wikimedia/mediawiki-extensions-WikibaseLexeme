<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormRequest {

	private $formId;

	public function __construct( FormId $formId ) {
		$this->formId = $formId;
	}

	public function getChangeOp(): ChangeOpRemoveForm {
		return new ChangeOpRemoveForm( $this->formId );
	}

	public function getFormId(): FormId {
		return $this->formId;
	}

}

<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\Domain\DataModel\FormId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequest {

	private $formId;
	private $changeOp;

	public function __construct( FormId $formId, ChangeOp $changeOp ) {
		$this->formId = $formId;
		$this->changeOp = $changeOp;
	}

	public function getChangeOp(): ChangeOp {
		return $this->changeOp;
	}

	public function getFormId(): FormId {
		return $this->formId;
	}

}

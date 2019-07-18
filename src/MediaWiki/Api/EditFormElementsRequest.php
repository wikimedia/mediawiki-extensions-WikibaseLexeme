<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequest {

	private $formId;
	private $changeOp;
	private $baseRevId;

	public function __construct( FormId $formId, ChangeOp $changeOp, $baseRevId ) {
		$this->formId = $formId;
		$this->changeOp = $changeOp;
		$this->baseRevId = $baseRevId;
	}

	public function getChangeOp(): ChangeOp {
		return $this->changeOp;
	}

	public function getFormId(): FormId {
		return $this->formId;
	}

	public function getBaseRevId() {
		return $this->baseRevId;
	}

}

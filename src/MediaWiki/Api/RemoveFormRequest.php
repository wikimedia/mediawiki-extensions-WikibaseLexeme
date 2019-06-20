<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormRequest {

	private $formId;

	/**
	 * @var int|null
	 */
	private $baseRevId;

	/**
	 * @param FormId $formId
	 * @param int|null $baseRevId
	 */
	public function __construct( FormId $formId, $baseRevId ) {
		$this->formId = $formId;
		$this->baseRevId = $baseRevId;
	}

	public function getChangeOp(): ChangeOpRemoveForm {
		return new ChangeOpRemoveForm( $this->formId );
	}

	public function getFormId(): FormId {
		return $this->formId;
	}

	/**
	 * @return int|null
	 */
	public function getBaseRevId() {
		return $this->baseRevId;
	}

}

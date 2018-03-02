<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpEditFormElements;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @license GPL-2.0-or-later
 */
class EditFormElementsRequest {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var TermList
	 */
	private $representations;

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	public function __construct(
		FormId $formId,
		TermList $representations,
		array $grammaticalFeatures
	) {
		$this->formId = $formId;
		$this->representations = $representations;
		$this->grammaticalFeatures = $grammaticalFeatures;
	}

	public function getChangeOp() {
		return new ChangeOpEditFormElements( $this->representations, $this->grammaticalFeatures );
	}

	public function getFormId() {
		return $this->formId;
	}

}

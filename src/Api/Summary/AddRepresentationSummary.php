<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0+
 */
class AddRepresentationSummary implements FormatableSummary {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var string[]
	 */
	private $addedRepresentations;

	/**
	 * @param FormId $formId
	 * @param string[] $addedRepresentations
	 */
	public function __construct( FormId $formId, array $addedRepresentations ) {
		$this->formId = $formId;
		$this->addedRepresentations = $addedRepresentations;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return null;
	}

	public function getMessageKey() {
		// Effective message key: wikibase-lexeme-summary-add-form-representations
		return 'add-form-representations';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return $this->addedRepresentations;
	}

}

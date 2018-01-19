<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0+
 */
class ChangeFormElementsSummary implements FormatableSummary {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @param FormId $formId
	 */
	public function __construct( FormId $formId ) {
		$this->formId = $formId;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return ''; // TODO: ????
	}

	public function getMessageKey() {
		/** @see "wikibaselexeme-summary-update-form-elements" message */
		return 'update-form-elements';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return [];
	}

}

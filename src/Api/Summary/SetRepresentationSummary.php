<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0-or-later
 */
class SetRepresentationSummary implements FormatableSummary {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var string[]
	 */
	private $representations;

	/**
	 * @param FormId $formId
	 * @param string[] $representations
	 */
	public function __construct( FormId $formId, array $representations ) {
		$this->formId = $formId;
		$this->representations = $representations;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return null;
	}

	public function getMessageKey() {
		// Effective message key: wikibase-lexeme-summary-set-form-representations
		return 'set-form-representations';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return $this->representations;
	}

}

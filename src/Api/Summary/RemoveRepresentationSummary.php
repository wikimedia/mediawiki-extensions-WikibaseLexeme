<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0-or-later
 */
class RemoveRepresentationSummary implements FormatableSummary {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var string[]
	 */
	private $removedRepresentations;

	/**
	 * @param FormId $formId
	 * @param string[] $removedRepresentations
	 */
	public function __construct( FormId $formId, array $removedRepresentations ) {
		$this->formId = $formId;
		$this->removedRepresentations = $removedRepresentations;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return count( $this->removedRepresentations ) === 1 ? key( $this->removedRepresentations ) : null;
	}

	public function getMessageKey() {
		// Effective message key: wikibase-lexeme-summary-remove-form-representations
		return 'remove-form-representations';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return $this->removedRepresentations;
	}

}

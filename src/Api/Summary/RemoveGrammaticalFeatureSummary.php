<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0+
 */
class RemoveGrammaticalFeatureSummary implements FormatableSummary {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var ItemId[]
	 */
	private $removedFeatures;

	/**
	 * @param FormId $formId
	 * @param ItemId[] $removedFeatures
	 */
	public function __construct( FormId $formId, array $removedFeatures ) {
		$this->formId = $formId;
		$this->removedFeatures = $removedFeatures;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return null;
	}

	public function getMessageKey() {
		// Effective message key: wikibase-lexeme-summary-remove-form-grammatical-features
		return 'remove-form-grammatical-features';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return $this->removedFeatures;
	}

}

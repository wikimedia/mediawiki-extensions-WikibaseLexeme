<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0+
 */
class AddGrammaticalFeatureSummary implements FormatableSummary {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var ItemId[]
	 */
	private $addedFeatures;

	/**
	 * @param FormId $formId
	 * @param ItemId[] $addedFeatures
	 */
	public function __construct( FormId $formId, array $addedFeatures ) {
		$this->formId = $formId;
		$this->addedFeatures = $addedFeatures;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return null;
	}

	public function getMessageKey() {
		/** @see "wikibaselexeme-summary-add-form-grammatical-features" message */
		return 'add-form-grammatical-features';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return $this->addedFeatures;
	}

}

<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0-or-later
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
		// Effective message key: wikibase-lexeme-summary-add-form-grammatical-features
		return 'add-form-grammatical-features';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return $this->addedFeatures;
	}

}

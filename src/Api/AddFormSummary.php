<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0-or-later
 */
class AddFormSummary implements FormatableSummary {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var Form
	 */
	private $addedForm;

	public function __construct( LexemeId $lexemeId, Form $addedForm ) {
		$this->lexemeId = $lexemeId;
		$this->addedForm = $addedForm;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return null;
	}

	public function getMessageKey() {
		/** @see "wikibase-lexeme-summary-add-form" message */
		return 'add-form';
	}

	public function getCommentArgs() {
		return [ $this->addedForm->getId()->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return array_values( $this->addedForm->getRepresentations()->toTextArray() );
	}

}

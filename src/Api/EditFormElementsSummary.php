<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0+
 */
class EditFormElementsSummary implements FormatableSummary {

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return null;
	}

	public function getMessageKey() {
		/** @see "wikibase-lexeme-summary-set-form" message */
		return 'set-form';
	}

	public function getCommentArgs() {
		return [];
	}

	public function getAutoSummaryArgs() {
		return [];
	}

}

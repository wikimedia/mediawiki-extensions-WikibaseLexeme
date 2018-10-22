<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class LexemeSaveFailedException extends MergingException {

	public function getErrorMessage(): Message {
		return new Message( 'wikibase-lexeme-mergelexemes-error-failed-save' );
	}

	public function getApiErrorCode() {
		return 'failed-save';
	}

}

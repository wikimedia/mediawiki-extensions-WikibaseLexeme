<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class DifferentLexicalCategoriesException extends MergingException {

	public function getErrorMessage(): Message {
		return new Message( 'wikibase-lexeme-mergelexemes-error-same-lexical-category' );
	}

	public function getApiErrorCode() {
		return 'failed-modify';
	}

}

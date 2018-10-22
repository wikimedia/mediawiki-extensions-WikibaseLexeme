<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class ModificationFailedException extends MergingException {

	public function getErrorMessage(): Message {
		return new Message( 'wikibase-lexeme-mergelexemes-error-failed-modify' );
	}

	public function getApiErrorCode() {
		return 'failed-modify';
	}

}

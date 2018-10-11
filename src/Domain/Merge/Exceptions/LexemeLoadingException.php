<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class LexemeLoadingException extends MergingException {

	public function getErrorMessage(): Message {
		return new Message( 'wikibase-lexeme-mergelexemes-error-cannot-load' );
	}

	public function getApiErrorCode() {
		return 'cant-load-entity-content';
	}

}

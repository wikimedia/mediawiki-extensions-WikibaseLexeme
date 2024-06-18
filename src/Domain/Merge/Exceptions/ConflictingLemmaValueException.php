<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 */
class ConflictingLemmaValueException extends MergingException {

	public function getErrorMessage(): Message {
		return new Message( 'wikibase-lexeme-mergelexemes-error-conflicting-lemma' );
	}

	public function getApiErrorCode() {
		return 'failed-modify';
	}

}

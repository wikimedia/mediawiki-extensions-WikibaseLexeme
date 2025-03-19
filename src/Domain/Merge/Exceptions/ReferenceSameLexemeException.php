<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 */
class ReferenceSameLexemeException extends MergingException {

	public function getErrorMessage(): Message {
		return new Message( 'wikibase-lexeme-mergelexemes-error-same-lexemes' );
	}

	public function getApiErrorCode(): string {
		return 'cant-merge-self';
	}

}

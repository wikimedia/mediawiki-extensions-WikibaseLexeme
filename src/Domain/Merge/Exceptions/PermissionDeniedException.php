<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class PermissionDeniedException extends MergingException {

	public function getErrorMessage(): Message {
		return new Message( 'wikibase-lexeme-mergelexemes-error-permission-denied' );
	}

	public function getApiErrorCode() {
		return 'permissiondenied';
	}

}

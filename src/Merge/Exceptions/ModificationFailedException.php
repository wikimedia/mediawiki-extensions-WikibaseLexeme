<?php

namespace Wikibase\Lexeme\Merge\Exceptions;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class ModificationFailedException extends MergingException {

	public function getErrorMessage(): Message {
		return new Message( 'wikibase-lexeme-mergelexemes-error-failed-modify' );
	}

}

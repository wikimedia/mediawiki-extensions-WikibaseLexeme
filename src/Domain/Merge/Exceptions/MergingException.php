<?php

namespace Wikibase\Lexeme\Domain\Merge\Exceptions;

use Message;
use RuntimeException;

/**
 * @license GPL-2.0-or-later
 */
abstract class MergingException extends RuntimeException {

	abstract public function getErrorMessage(): Message;

	/**
	 * @return string
	 */
	abstract public function getApiErrorCode();

}

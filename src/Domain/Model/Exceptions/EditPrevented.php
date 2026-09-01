<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Domain\Model\Exceptions;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class EditPrevented extends Exception {

	public function __construct(
		public readonly string $reason,
		public readonly array $context = [] ) {
	}
}

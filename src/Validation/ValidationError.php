<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Validation;

/**
 * @license GPL-2.0-or-later
 */
class ValidationError {

	public function __construct(
		public readonly string $code,
		public readonly array $context = [],
	) {
	}

}

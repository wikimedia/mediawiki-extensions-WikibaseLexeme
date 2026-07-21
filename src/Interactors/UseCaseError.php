<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Interactors;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseError extends RuntimeException {

	public const string LEXEME_NOT_FOUND = 'lexeme-not-found';

	public function __construct(
		public readonly string $errorCode,
		public readonly string $errorMessage,
	) {
		parent::__construct();
	}

	public static function newLexemeNotFound(): self {
		return new self(
			self::LEXEME_NOT_FOUND,
			'The requested lexeme does not exist',
		);
	}
}

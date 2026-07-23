<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Interactors;

use LogicException;
use RuntimeException;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseError extends RuntimeException {

	public const string LEXEME_NOT_FOUND = 'lexeme-not-found';
	public const string INVALID_PATH_PARAMETER = 'invalid-path-parameter';

	public const string CONTEXT_PARAMETER = 'parameter';

	private const array EXPECTED_CONTEXT_KEYS = [
		self::LEXEME_NOT_FOUND => [],
		self::INVALID_PATH_PARAMETER => [ self::CONTEXT_PARAMETER ],
	];

	public function __construct(
		public readonly string $errorCode,
		public readonly string $errorMessage,
		public readonly array $context = [],
	) {
		parent::__construct();

		if ( !array_key_exists( $errorCode, self::EXPECTED_CONTEXT_KEYS ) ) {
			throw new LogicException( "Unknown error code: '$errorCode'" );
		}

		$contextKeys = array_keys( $context );
		$unexpectedContext = array_values( array_diff( $contextKeys, self::EXPECTED_CONTEXT_KEYS[$errorCode] ) );
		if ( $unexpectedContext ) {
			throw new LogicException(
				"Error context for '$errorCode' should not contain keys: " . json_encode( $unexpectedContext )
			);
		}
		$missingContext = array_values( array_diff( self::EXPECTED_CONTEXT_KEYS[$errorCode], $contextKeys ) );
		if ( $missingContext ) {
			throw new LogicException(
				"Error context for '$errorCode' should contain keys: " . json_encode( $missingContext )
			);
		}
	}

	public static function newLexemeNotFound(): self {
		return new self(
			self::LEXEME_NOT_FOUND,
			'The requested lexeme does not exist',
		);
	}

	public static function newInvalidPathParameter( string $parameterName ): self {
		return new self(
			self::INVALID_PATH_PARAMETER,
			"Invalid path parameter: '$parameterName'",
			[ self::CONTEXT_PARAMETER => $parameterName ],
		);
	}
}

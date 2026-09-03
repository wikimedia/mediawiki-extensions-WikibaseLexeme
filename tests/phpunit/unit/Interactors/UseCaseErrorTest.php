<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @covers \Wikibase\Lexeme\Interactors\UseCaseError
 *
 * @license GPL-2.0-or-later
 */
class UseCaseErrorTest extends TestCase {

	/**
	 * @dataProvider provideValidUseCaseErrorData
	 */
	public function testHappyPath( string $errorCode, string $errorMessage, array $errorContext = [] ): void {
		$useCaseError = new UseCaseError( $errorCode, $errorMessage, $errorContext );

		$this->assertSame( $errorCode, $useCaseError->errorCode );
		$this->assertSame( $errorMessage, $useCaseError->errorMessage );
		$this->assertSame( $errorContext, $useCaseError->context );
	}

	public static function provideValidUseCaseErrorData(): Generator {
		yield 'valid error without context' => [
			UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
			'Access is denied for an unknown reason',
		];

		yield 'valid error with context' => [
			UseCaseError::INVALID_PATH_PARAMETER,
			"Invalid path parameter: 'lexeme_id'",
			[ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ],
		];

		yield 'without optional context' => [
			UseCaseError::PERMISSION_DENIED,
			'Access to resource is denied',
			[ UseCaseError::CONTEXT_DENIAL_REASON => 'some-reason' ],
		];

		yield 'with optional context' => [
			UseCaseError::PERMISSION_DENIED,
			'Access to resource is denied',
			[
				UseCaseError::CONTEXT_DENIAL_REASON => 'some-other-reason',
				UseCaseError::CONTEXT_DENIAL_CONTEXT => [ 'some' => 'context' ],
			],
		];
	}

	/**
	 * @dataProvider provideInvalidUseCaseErrorData
	 */
	public function testInvalidInstantiation(
		string $errorCode,
		string $errorMessage,
		array $errorContext = []
	): void {
		$this->expectException( LogicException::class );
		new UseCaseError( $errorCode, $errorMessage, $errorContext );
	}

	public static function provideInvalidUseCaseErrorData(): Generator {
		yield 'error code not defined' => [ 'not-a-valid-error-code', 'not a valid error code' ];

		yield 'error context contains incorrect key' => [
			UseCaseError::INVALID_PATH_PARAMETER,
			'incorrect context key',
			[ 'incorrect-context-key' => 'potato' ],
		];

		yield 'error context is missing expected keys' => [
			UseCaseError::INVALID_PATH_PARAMETER,
			'error context key is missing',
		];

		yield 'wrong path context field name' => [
			UseCaseError::REFERENCED_RESOURCE_NOT_FOUND,
			'The referenced resource does not exist',
			[ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ],
		];
	}
}

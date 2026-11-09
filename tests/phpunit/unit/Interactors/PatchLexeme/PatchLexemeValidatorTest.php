<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\PatchLexeme;

use Generator;
use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\DataAccess\JsonDiffJsonPatchValidator;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeRequest;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;

/**
 * @covers \Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeValidator
 * @covers \Wikibase\Lexeme\DataAccess\JsonDiffJsonPatchValidator
 *
 * @license GPL-2.0-or-later
 */
class PatchLexemeValidatorTest extends MediaWikiUnitTestCase {

	public function testGivenValidRequest_exposesLexemeId(): void {
		$validator = $this->newValidator();

		$validator->validate( $this->newRequest( 'L123' ) );

		$this->assertEquals( new LexemeId( 'L123' ), $validator->getValidatedLexemeId() );
	}

	public function testGivenValidRequest_validatesEditMetadata(): void {
		$editTags = [ 'allowed tag' ];
		$comment = 'user comment';
		$editMetadataRequestValidator = $this->createMock( EditMetadataRequestValidator::class );
		$editMetadataRequestValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $editTags, $comment );

		$this->newValidator( $editMetadataRequestValidator )
			->validate( self::newRequest( 'L123', [], $editTags, $comment ) );
	}

	public function testGivenInvalidLexemeId_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate( $this->newRequest( 'not-a-lexeme-id' ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ], $e->context );
		}
	}

	/**
	 * @dataProvider invalidPatchProvider
	 */
	public function testGivenInvalidPatch_throwsUseCaseError(
		array $patch,
		string $expectedErrorCode,
		array $expectedContext,
	): void {
		try {
			$this->newValidator()->validate( $this->newRequest( 'L123', $patch ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->errorCode );
			$this->assertSame( $expectedContext, $e->context );
		}
	}

	public static function invalidPatchProvider(): Generator {
		$validOperation = [ 'op' => 'add', 'path' => '/lemmas/en', 'value' => 'potato' ];

		yield 'not a JSON Patch' => [
			[ 'foo' => 'this is not a valid JSON Patch' ],
			UseCaseError::INVALID_VALUE,
			[ UseCaseError::CONTEXT_PATH => '/patch' ],
		];
		yield "missing 'op'" => [
			[ $validOperation, [ 'path' => '/lemmas/en', 'value' => 'potato' ] ],
			UseCaseError::MISSING_FIELD,
			[ UseCaseError::CONTEXT_PATH => '/patch/1', UseCaseError::CONTEXT_FIELD => 'op' ],
		];
		yield "missing 'path'" => [
			[ [ 'op' => 'remove' ] ],
			UseCaseError::MISSING_FIELD,
			[ UseCaseError::CONTEXT_PATH => '/patch/0', UseCaseError::CONTEXT_FIELD => 'path' ],
		];
		yield "missing 'value'" => [
			[ [ 'op' => 'add', 'path' => '/lemmas/en' ] ],
			UseCaseError::MISSING_FIELD,
			[ UseCaseError::CONTEXT_PATH => '/patch/0', UseCaseError::CONTEXT_FIELD => 'value' ],
		];
		yield "missing 'from'" => [
			[ [ 'op' => 'move', 'path' => '/lemmas/en' ] ],
			UseCaseError::MISSING_FIELD,
			[ UseCaseError::CONTEXT_PATH => '/patch/0', UseCaseError::CONTEXT_FIELD => 'from' ],
		];
		yield "invalid 'op'" => [
			[ $validOperation, [ 'op' => 'foobar', 'path' => '/lemmas/en', 'value' => 'potato' ] ],
			UseCaseError::INVALID_VALUE,
			[ UseCaseError::CONTEXT_PATH => '/patch/1/op' ],
		];
		yield "'op' is not a string" => [
			[ [ 'op' => [ 'foo' => [ 'bar' ], 'baz' => 42 ], 'path' => '/lemmas/en', 'value' => 'potato' ] ],
			UseCaseError::INVALID_VALUE,
			[ UseCaseError::CONTEXT_PATH => '/patch/0/op' ],
		];
		yield "'path' is not a string" => [
			[ [ 'op' => 'add', 'path' => [ 'foo' => [ 'bar' ], 'baz' => 42 ], 'value' => 'potato' ] ],
			UseCaseError::INVALID_VALUE,
			[ UseCaseError::CONTEXT_PATH => '/patch/0/path' ],
		];
		yield "'from' is not a string" => [
			[ [ 'op' => 'move', 'from' => [ 'foo' => [ 'bar' ], 'baz' => 42 ], 'path' => '/lemmas/en' ] ],
			UseCaseError::INVALID_VALUE,
			[ UseCaseError::CONTEXT_PATH => '/patch/0/from' ],
		];
	}

	public function testGivenValidateNotCalled_getValidatedLexemeIdThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedLexemeId();
	}

	private function newRequest(
		string $lexemeId,
		array $patch = [],
		array $editTags = [],
		?string $comment = null
	): PatchLexemeRequest {
		return new PatchLexemeRequest( $lexemeId, $patch, $editTags, false, $comment, null );
	}

	private function newValidator(
		?EditMetadataRequestValidator $editMetadataRequestValidator = null,
	): PatchLexemeValidator {
		return new PatchLexemeValidator(
			new LexemeIdValidator(),
			new JsonDiffJsonPatchValidator(),
			$editMetadataRequestValidator ?? $this->createStub( EditMetadataRequestValidator::class ),
		);
	}
}

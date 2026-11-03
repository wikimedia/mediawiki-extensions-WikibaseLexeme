<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\PatchLexeme;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeRequest;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;

/**
 * @covers \Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeValidator
 *
 * @license GPL-2.0-or-later
 */
class PatchLexemeValidatorTest extends MediaWikiUnitTestCase {

	public function testGivenValidRequest_exposesLexemeId(): void {
		$validator = new PatchLexemeValidator( new LexemeIdValidator() );

		$validator->validate( $this->newRequest( 'L123' ) );

		$this->assertEquals( new LexemeId( 'L123' ), $validator->getValidatedLexemeId() );
	}

	public function testGivenInvalidLexemeId_throwsUseCaseError(): void {
		$validator = new PatchLexemeValidator( new LexemeIdValidator() );

		try {
			$validator->validate( $this->newRequest( 'not-a-lexeme-id' ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ], $e->context );
		}
	}

	public function testGivenValidateNotCalled_getValidatedLexemeIdThrows(): void {
		$this->expectException( LogicException::class );

		( new PatchLexemeValidator( new LexemeIdValidator() ) )->getValidatedLexemeId();
	}

	private function newRequest( string $lexemeId ): PatchLexemeRequest {
		return new PatchLexemeRequest( $lexemeId, [], [], false, null );
	}

}

<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\UseCaseRequestValidation;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;

/**
 * @covers \Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator
 *
 * @license GPL-2.0-or-later
 */
class LexemeIdValidatorTest extends MediaWikiUnitTestCase {

	public function testGivenValidId_returnsLexemeId(): void {
		$this->assertEquals( new LexemeId( 'L123' ), ( new LexemeIdValidator() )->validate( 'L123' ) );
	}

	public function testGivenInvalidId_throwsUseCaseError(): void {
		try {
			( new LexemeIdValidator() )->validate( 'not-a-lexeme-id' );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->errorCode );
			$this->assertSame( "Invalid path parameter: 'lexeme_id'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ], $e->context );
		}
	}

}

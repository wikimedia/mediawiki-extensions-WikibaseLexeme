<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\GetLexeme;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeRequest;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;

/**
 * @covers \Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeValidator
 *
 * @license GPL-2.0-or-later
 */
class GetLexemeValidatorTest extends MediaWikiUnitTestCase {

	public function testGivenValidRequest_exposesLexemeId(): void {
		$validator = new GetLexemeValidator( new LexemeIdValidator() );

		$validator->validate( new GetLexemeRequest( 'L123' ) );

		$this->assertEquals( new LexemeId( 'L123' ), $validator->getValidatedLexemeId() );
	}

	public function testGivenInvalidLexemeId_throwsUseCaseError(): void {
		$validator = new GetLexemeValidator( new LexemeIdValidator() );

		try {
			$validator->validate( new GetLexemeRequest( 'not-a-lexeme-id' ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->errorCode );
		}
	}

	public function testGivenValidateNotCalled_getValidatedLexemeIdThrows(): void {
		$this->expectException( LogicException::class );

		( new GetLexemeValidator( new LexemeIdValidator() ) )->getValidatedLexemeId();
	}

}

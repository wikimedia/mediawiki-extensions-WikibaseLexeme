<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\CreateLexeme;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeRequest;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @covers \Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeValidator
 *
 * @license GPL-2.0-or-later
 */
class CreateLexemeValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_LEXEME = [
		'lemmas' => [ 'en' => 'potato' ],
		'lexical_category' => 'Q1',
		'language' => 'Q2',
	];

	public function testGivenValidRequest_passes(): void {
		( new CreateLexemeValidator() )->validate( new CreateLexemeRequest( self::VALID_LEXEME ) );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * @dataProvider provideMissingField
	 */
	public function testGivenMissingField_throwsUseCaseError( string $field ): void {
		$serialization = self::VALID_LEXEME;
		unset( $serialization[$field] );

		try {
			( new CreateLexemeValidator() )->validate( new CreateLexemeRequest( $serialization ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::MISSING_FIELD, $e->errorCode );
			$this->assertSame( 'Required field missing', $e->errorMessage );
			$this->assertSame(
				[ UseCaseError::CONTEXT_PATH => '/lexeme', UseCaseError::CONTEXT_FIELD => $field ],
				$e->context
			);
		}
	}

	public static function provideMissingField(): iterable {
		yield 'lemmas' => [ 'lemmas' ];
		yield 'lexical_category' => [ 'lexical_category' ];
		yield 'language' => [ 'language' ];
	}

	public function testGivenEmptyLemmas_throwsUseCaseError(): void {
		try {
			( new CreateLexemeValidator() )->validate( new CreateLexemeRequest(
				array_merge( self::VALID_LEXEME, [ 'lemmas' => [] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( "Invalid value at '/lexeme/lemmas'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/lexeme/lemmas' ], $e->context );
		}
	}

}

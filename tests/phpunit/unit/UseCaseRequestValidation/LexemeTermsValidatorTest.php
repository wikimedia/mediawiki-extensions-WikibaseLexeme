<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\UseCaseRequestValidation;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;
use Wikibase\Lexeme\Validation\LexemeTermLanguageCodeValidator;

/**
 * @covers \Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator
 *
 * @license GPL-2.0-or-later
 */
class LexemeTermsValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_LANGUAGE_CODES = [ 'en', 'de' ];

	private const MAX_LENGTH = 42;

	private const PATH = '/some/terms';

	public function testGivenValidTerms_returnsTermList(): void {
		$this->assertEquals(
			new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ),
			$this->newValidator()->validateAndDeserialize( [ 'en' => 'potato', 'de' => 'Kartoffel' ], self::PATH )
		);
	}

	/**
	 * @dataProvider provideTextWithSurroundingWhitespace
	 */
	public function testGivenTextWithSurroundingWhitespace_trims( string $text, string $expectedText ): void {
		$this->assertEquals(
			new TermList( [ new Term( 'en', $expectedText ) ] ),
			$this->newValidator()->validateAndDeserialize( [ 'en' => $text ], self::PATH )
		);
	}

	public static function provideTextWithSurroundingWhitespace(): iterable {
		yield 'leading whitespace' => [ ' potato', 'potato' ];
		yield 'trailing whitespace' => [ 'potato ', 'potato' ];
		yield 'surrounding whitespace incl. vertical' => [ "  sweet potato \n", 'sweet potato' ];
	}

	/**
	 * @dataProvider provideInvalidTerms
	 */
	public function testGivenInvalidTerms_throwsUseCaseError( mixed $terms ): void {
		try {
			$this->newValidator()->validateAndDeserialize( $terms, self::PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( "Invalid value at '" . self::PATH . "'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => self::PATH ], $e->context );
		}
	}

	public static function provideInvalidTerms(): iterable {
		yield 'empty map' => [ [] ];
		yield 'string' => [ 'potato' ];
		yield 'int' => [ 42 ];
		yield 'null' => [ null ];
		yield 'list' => [ [ 'potato' ] ];
	}

	public function testGivenInvalidLanguageCode_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( [ 'xyz' => 'potato' ], self::PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_KEY, $e->errorCode );
			$this->assertSame( "Invalid key 'xyz' in '" . self::PATH . "'", $e->errorMessage );
			$this->assertSame(
				[ UseCaseError::CONTEXT_PATH => self::PATH, UseCaseError::CONTEXT_KEY => 'xyz' ],
				$e->context
			);
		}
	}

	/**
	 * @dataProvider provideInvalidText
	 */
	public function testGivenInvalidText_throwsUseCaseError( mixed $text ): void {
		try {
			$this->newValidator()->validateAndDeserialize( [ 'en' => $text ], self::PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( "Invalid value at '" . self::PATH . "/en'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => self::PATH . '/en' ], $e->context );
		}
	}

	public static function provideInvalidText(): iterable {
		yield 'int' => [ 42 ];
		yield 'null' => [ null ];
		yield 'array' => [ [ 'potato' ] ];
		yield 'empty string' => [ '' ];
		yield 'whitespace only' => [ '   ' ];
		yield 'tab inside' => [ "pot\tato" ];
		yield 'vertical whitespace inside' => [ "pot\nato" ];
	}

	public function testGivenTextTooLong_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize(
				[ 'en' => str_repeat( 'x', self::MAX_LENGTH + 1 ) ],
				self::PATH
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::VALUE_TOO_LONG, $e->errorCode );
			$this->assertSame( 'The input value is too long', $e->errorMessage );
			$this->assertSame(
				[
					UseCaseError::CONTEXT_PATH => self::PATH . '/en',
					UseCaseError::CONTEXT_LIMIT => self::MAX_LENGTH,
				],
				$e->context
			);
		}
	}

	public function testGivenTextOfMaxLengthWithSurroundingWhitespace_passes(): void {
		$text = str_repeat( 'x', self::MAX_LENGTH );

		$this->assertEquals(
			new TermList( [ new Term( 'en', $text ) ] ),
			$this->newValidator()->validateAndDeserialize( [ 'en' => " $text " ], self::PATH )
		);
	}

	public function testGivenInvalidLanguageCodeAndInvalidText_reportsInvalidLanguageCode(): void {
		try {
			$this->newValidator()->validateAndDeserialize( [ 'xyz' => '' ], self::PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_KEY, $e->errorCode );
		}
	}

	public function testGivenMultipleInvalidTerms_reportsFirst(): void {
		try {
			$this->newValidator()->validateAndDeserialize( [ 'en' => '', 'xyz' => 'potato' ], self::PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => self::PATH . '/en' ], $e->context );
		}
	}

	private function newValidator(): LexemeTermsValidator {
		return new LexemeTermsValidator(
			new class( self::VALID_LANGUAGE_CODES ) implements LexemeTermLanguageCodeValidator {
				public function __construct( private array $validLanguageCodes ) {
				}

				public function isValid( string $languageCode ): bool {
					return in_array( $languageCode, $this->validLanguageCodes );
				}
			},
			self::MAX_LENGTH,
		);
	}

}

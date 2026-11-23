<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Validation;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Validation\LexemeTermLanguageCodeValidator;
use Wikibase\Lexeme\Validation\LexemeTermsValidator;
use Wikibase\Lexeme\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\Validation\LexemeTermsValidator
 *
 * @license GPL-2.0-or-later
 */
class LexemeTermsValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_LANGUAGE_CODES = [ 'en', 'de' ];

	private const MAX_LENGTH = 42;

	private const PATH = '/some/terms';

	private LexemeTermsValidator $validator;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = $this->newValidator();
	}

	public function testGivenValidTerms_returnsTermList(): void {
		$this->validator->validate( [ 'en' => 'potato', 'de' => 'Kartoffel' ], self::PATH );
		$this->assertEquals(
			new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ),
			$this->validator->getValidatedTerms()
		);
	}

	/**
	 * @dataProvider provideTextWithSurroundingWhitespace
	 */
	public function testGivenTextWithSurroundingWhitespace_trims( string $text, string $expectedText ): void {
		$this->validator->validate( [ 'en' => $text ], self::PATH );
		$this->assertEquals(
			new TermList( [ new Term( 'en', $expectedText ) ] ),
			$this->validator->getValidatedTerms()
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
	public function testGivenInvalidTerms_returnsValidationError( mixed $terms ): void {
		$this->assertEquals( new ValidationError(
			LexemeTermsValidator::CODE_INVALID_VALUE,
			[
				LexemeTermsValidator::CONTEXT_PATH => self::PATH,
				LexemeTermsValidator::CONTEXT_VALUE => $terms,
			]
		), $this->validator->validate( $terms, self::PATH ) );
	}

	public static function provideInvalidTerms(): iterable {
		yield 'empty map' => [ [] ];
		yield 'string' => [ 'potato' ];
		yield 'int' => [ 42 ];
		yield 'null' => [ null ];
		yield 'list' => [ [ 'potato' ] ];
	}

	public function testGivenInvalidLanguageCode_returnsValidationError(): void {
		$invalidLanguageCode = 'xyz';
		$this->assertEquals( new ValidationError(
			LexemeTermsValidator::CODE_INVALID_KEY,
			[
				LexemeTermsValidator::CONTEXT_PATH => self::PATH,
				LexemeTermsValidator::CONTEXT_KEY => $invalidLanguageCode,
			]
		), $this->validator->validate( [ $invalidLanguageCode => 'potato' ], self::PATH ) );
	}

	/**
	 * @dataProvider provideInvalidText
	 */
	public function testGivenInvalidText_throwsUseCaseError( mixed $text ): void {
		$this->assertEquals( new ValidationError(
			LexemeTermsValidator::CODE_INVALID_VALUE,
			[
				LexemeTermsValidator::CONTEXT_PATH => self::PATH . '/en',
				LexemeTermsValidator::CONTEXT_VALUE => $text,
			]
		), $this->validator->validate( [ 'en' => $text ], self::PATH ) );
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
		$this->assertEquals( new ValidationError(
			LexemeTermsValidator::CODE_VALUE_TOO_LONG,
			[
				LexemeTermsValidator::CONTEXT_PATH => self::PATH . '/en',
				LexemeTermsValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
			]
		), $this->validator->validate( [ 'en' => str_repeat( 'x', self::MAX_LENGTH + 1 ) ], self::PATH ) );
	}

	public function testGivenTextOfMaxLengthWithSurroundingWhitespace_passes(): void {
		$text = str_repeat( 'x', self::MAX_LENGTH );

		$this->assertNull(
			$this->newValidator()->validate( [ 'en' => " $text " ], self::PATH )
		);
	}

	public function testGivenInvalidLanguageCodeAndInvalidText_reportsInvalidLanguageCode(): void {
		$this->assertSame(
			LexemeTermsValidator::CODE_INVALID_KEY,
			$this->validator->validate( [ 'xyz' => '' ], self::PATH )->code
		);
	}

	public function testGivenMultipleInvalidTerms_reportsFirst(): void {
		$this->assertSame(
			LexemeTermsValidator::CODE_INVALID_VALUE,
			$this->validator->validate( [ 'en' => '', 'xyz' => 'potato' ], self::PATH )->code
		);
	}

	public function testGivenValidateNotCalled_getValidatedTermsThrows(): void {
		$this->expectException( LogicException::class );

		$this->validator->getValidatedTerms();
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

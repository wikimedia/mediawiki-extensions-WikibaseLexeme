<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\CreateLexeme;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\Domain\Model\CreateLexemeEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeRequest;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Lexeme\Validation\ItemExistenceChecker;
use Wikibase\Lexeme\Validation\LemmaLanguageCodeValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeValidator
 *
 * @license GPL-2.0-or-later
 */
class CreateLexemeValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_LANGUAGE_CODES = [ 'en', 'de' ];

	private const EXISTING_ITEM_IDS = [ 'Q1', 'Q2' ];

	private const VALID_LEXEME = [
		'lemmas' => [ 'en' => 'potato' ],
		'lexical_category' => 'Q1',
		'language' => 'Q2',
	];

	public function testGivenValidRequest_exposesLexeme(): void {
		$statements = new StatementList(
			new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) )
		);
		$validator = $this->newValidator( $this->newStatementsValidator( $statements ) );

		$validator->validateAndDeserialize( self::newRequest(
			array_merge( self::VALID_LEXEME, [
				'lemmas' => [ 'en' => 'potato', 'de' => 'Kartoffel' ],
				'statements' => [ 'P123' => [ [ 'some' => 'statement' ] ] ],
			] )
		) );

		$this->assertEquals(
			new LexemeWriteModel(
				null,
				new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ),
				new ItemId( self::VALID_LEXEME['lexical_category'] ),
				new ItemId( self::VALID_LEXEME['language'] ),
				$statements,
			),
			$validator->getValidatedLexeme()
		);
	}

	/**
	 * @dataProvider provideInvalidItemId
	 */
	public function testGivenInvalidItemId_throwsUseCaseError( string $field, mixed $value ): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ $field => $value ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( "Invalid value at '/lexeme/$field'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => "/lexeme/$field" ], $e->context );
		}
	}

	public static function provideInvalidItemId(): iterable {
		foreach ( [ 'lexical_category', 'language' ] as $field ) {
			yield "$field: int" => [ $field, 42 ];
			yield "$field: null" => [ $field, null ];
			yield "$field: array" => [ $field, [ 'Q1' ] ];
			yield "$field: empty string" => [ $field, '' ];
			yield "$field: not an id" => [ $field, 'potato' ];
			yield "$field: property id" => [ $field, 'P123' ];
			yield "$field: lexeme id" => [ $field, 'L1' ];
		}
	}

	/**
	 * @dataProvider provideNonexistentItemField
	 */
	public function testGivenNonexistentItem_throwsUseCaseError( string $field ): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ $field => 'Q999' ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::REFERENCED_RESOURCE_NOT_FOUND, $e->errorCode );
			$this->assertSame( 'The referenced resource does not exist', $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => "/lexeme/$field" ], $e->context );
		}
	}

	public static function provideNonexistentItemField(): iterable {
		yield 'lexical_category' => [ 'lexical_category' ];
		yield 'language' => [ 'language' ];
	}

	/**
	 * @dataProvider provideLemmaTextWithSurroundingWhitespace
	 */
	public function testGivenLemmaTextWithSurroundingWhitespace_trims( string $text, string $expectedText ): void {
		$validator = $this->newValidator();

		$validator->validateAndDeserialize( self::newRequest(
			array_merge( self::VALID_LEXEME, [ 'lemmas' => [ 'en' => $text ] ] )
		) );

		$this->assertEquals(
			new TermList( [ new Term( 'en', $expectedText ) ] ),
			$validator->getValidatedLexeme()->getLemmas()
		);
	}

	public static function provideLemmaTextWithSurroundingWhitespace(): iterable {
		yield 'leading whitespace' => [ ' potato', 'potato' ];
		yield 'trailing whitespace' => [ 'potato ', 'potato' ];
		yield 'surrounding whitespace incl. vertical' => [ "  sweet potato \n", 'sweet potato' ];
	}

	public function testGivenValidateAndDeserializeNotCalled_getValidatedLexemeThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedLexeme();
	}

	/**
	 * @dataProvider provideMissingField
	 */
	public function testGivenMissingField_throwsUseCaseError( string $field ): void {
		$serialization = self::VALID_LEXEME;
		unset( $serialization[$field] );

		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest( $serialization ) );
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

	/**
	 * @dataProvider provideInvalidLemmas
	 */
	public function testGivenInvalidLemmas_throwsUseCaseError( mixed $lemmas ): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'lemmas' => $lemmas ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( "Invalid value at '/lexeme/lemmas'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/lexeme/lemmas' ], $e->context );
		}
	}

	public static function provideInvalidLemmas(): iterable {
		yield 'empty map' => [ [] ];
		yield 'string' => [ 'potato' ];
		yield 'int' => [ 42 ];
		yield 'list' => [ [ 'potato' ] ];
	}

	public function testGivenInvalidLanguageCode_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'lemmas' => [ 'xyz' => 'potato' ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_KEY, $e->errorCode );
			$this->assertSame( "Invalid key 'xyz' in '/lexeme/lemmas'", $e->errorMessage );
			$this->assertSame(
				[ UseCaseError::CONTEXT_PATH => '/lexeme/lemmas', UseCaseError::CONTEXT_KEY => 'xyz' ],
				$e->context
			);
		}
	}

	/**
	 * @dataProvider provideInvalidLemmaText
	 */
	public function testGivenInvalidLemmaText_throwsUseCaseError( mixed $text ): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'lemmas' => [ 'en' => $text ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( "Invalid value at '/lexeme/lemmas/en'", $e->errorMessage );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/lexeme/lemmas/en' ], $e->context );
		}
	}

	public static function provideInvalidLemmaText(): iterable {
		yield 'int' => [ 42 ];
		yield 'null' => [ null ];
		yield 'array' => [ [ 'potato' ] ];
		yield 'empty string' => [ '' ];
		yield 'whitespace only' => [ '   ' ];
		yield 'tab inside' => [ "pot\tato" ];
		yield 'vertical whitespace inside' => [ "pot\nato" ];
	}

	public function testGivenLemmaTextTooLong_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [
					'lemmas' => [ 'en' => str_repeat( 'x', LemmaTermValidator::LEMMA_MAX_LENGTH + 1 ) ],
				] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::VALUE_TOO_LONG, $e->errorCode );
			$this->assertSame( 'The input value is too long', $e->errorMessage );
			$this->assertSame(
				[
					UseCaseError::CONTEXT_PATH => '/lexeme/lemmas/en',
					UseCaseError::CONTEXT_LIMIT => LemmaTermValidator::LEMMA_MAX_LENGTH,
				],
				$e->context
			);
		}
	}

	public function testGivenInvalidLanguageCodeAndInvalidText_reportsInvalidLanguageCode(): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'lemmas' => [ 'xyz' => '' ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_KEY, $e->errorCode );
		}
	}

	public function testGivenMultipleInvalidLemmas_reportsFirst(): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'lemmas' => [ 'en' => '', 'xyz' => 'potato' ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/lexeme/lemmas/en' ], $e->context );
		}
	}

	public function testGivenStatementsNotAnArray_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'statements' => 'potato' ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/lexeme/statements' ], $e->context );
		}
	}

	public function testGivenStatementsNull_treatedAsAbsent(): void {
		$validator = $this->newValidator();

		$validator->validateAndDeserialize( self::newRequest(
			array_merge( self::VALID_LEXEME, [ 'statements' => null ] )
		) );

		$this->assertTrue( $validator->getValidatedLexeme()->getStatements()->isEmpty() );
	}

	public function testGivenStatementsValidationError_throwsUseCaseError(): void {
		$validator = $this->newValidator( $this->newStatementsValidatorWithError(
			new ValidationError( StatementValidator::CODE_MISSING_FIELD, [
				StatementValidator::CONTEXT_PATH => '/lexeme/statements/P123/0',
				StatementValidator::CONTEXT_FIELD => 'value',
			] )
		) );

		try {
			$validator->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'statements' => [ 'P123' => [] ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( '/lexeme/statements/P123/0', 'value' ), $e );
		}
	}

	public function testGivenValidRequestWithEditMetadata_exposesEditMetadata(): void {
		$validator = $this->newValidator();

		$validator->validateAndDeserialize( new CreateLexemeRequest(
			self::VALID_LEXEME,
			[ 'allowed tag' ],
			true,
			'user comment',
			null,
		) );

		$this->assertEquals(
			new EditMetadata( [ 'allowed tag' ], true, new CreateLexemeEditSummary( 'user comment' ) ),
			$validator->getValidatedEditMetadata()
		);
	}

	public function testGivenInvalidEditMetadata_throwsUseCaseError(): void {
		$expectedError = UseCaseError::newInvalidValue( '/tags/1' );
		$editMetadataRequestValidator = $this->createStub( EditMetadataRequestValidator::class );
		$editMetadataRequestValidator->method( 'validate' )->willThrowException( $expectedError );

		try {
			$this->newValidator( editMetadataRequestValidator: $editMetadataRequestValidator )
				->validateAndDeserialize( self::newRequest( self::VALID_LEXEME ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function testGivenValidateAndDeserializeNotCalled_getValidatedEditMetadataThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedEditMetadata();
	}

	private static function newRequest( array $lexeme ): CreateLexemeRequest {
		return new CreateLexemeRequest( $lexeme, [], false, null, null );
	}

	private function newValidator(
		?StatementsValidator $statementsValidator = null,
		?EditMetadataRequestValidator $editMetadataRequestValidator = null,
	): CreateLexemeValidator {
		return new CreateLexemeValidator(
			new class( self::VALID_LANGUAGE_CODES ) implements LemmaLanguageCodeValidator {
				public function __construct( private array $validLanguageCodes ) {
				}

				public function isValid( string $languageCode ): bool {
					return in_array( $languageCode, $this->validLanguageCodes );
				}
			},
			new class( self::EXISTING_ITEM_IDS ) implements ItemExistenceChecker {
				public function __construct( private array $existingItemIds ) {
				}

				public function exists( ItemId $itemId ): bool {
					return in_array( $itemId->getSerialization(), $this->existingItemIds );
				}
			},
			$statementsValidator ?? $this->newStatementsValidator( new StatementList() ),
			new StatementsValidationErrorConverter(),
			LemmaTermValidator::LEMMA_MAX_LENGTH,
			$editMetadataRequestValidator ?? $this->createStub( EditMetadataRequestValidator::class ),
		);
	}

	private function newStatementsValidator( StatementList $validatedStatements ): StatementsValidator {
		$statementsValidator = $this->createStub( StatementsValidator::class );
		$statementsValidator->method( 'getValidatedStatements' )->willReturn( $validatedStatements );

		return $statementsValidator;
	}

	private function newStatementsValidatorWithError( ValidationError $error ): StatementsValidator {
		$statementsValidator = $this->createStub( StatementsValidator::class );
		$statementsValidator->method( 'validateNewStatements' )->willReturn( $error );

		return $statementsValidator;
	}

}

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
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\CreateLexemeEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\WriteModel\LexemeToCreate;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeRequest;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\FormValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\ItemIdValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\SenseValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Lexeme\Validation\ItemExistenceChecker;
use Wikibase\Lexeme\Validation\LexemeTermLanguageCodeValidator;
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

	private const VALID_SENSE = [ 'glosses' => [ 'en' => 'a starchy tuber' ] ];

	private const VALID_FORM = [
		'grammatical_features' => [ 'Q1' ],
		'representations' => [ 'en' => 'potatoes' ],
	];

	public function testGivenValidRequest_exposesLexeme(): void {
		$statements = new StatementList(
			new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) )
		);
		$sense = new BlankSense();
		$senseValidator = $this->createMock( SenseValidator::class );
		$senseValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( self::VALID_SENSE, '/lexeme/senses/0' )
			->willReturn( $sense );

		$form = new BlankForm();
		$formValidator = $this->createMock( FormValidator::class );
		$formValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( self::VALID_FORM, '/lexeme/forms/0' )
			->willReturn( $form );

		$validator = $this->newValidator(
			$this->newStatementsValidator( $statements ),
			formValidator: $formValidator,
			senseValidator: $senseValidator
		);

		$validator->validateAndDeserialize( self::newRequest(
			array_merge( self::VALID_LEXEME, [
				'lemmas' => [ 'en' => 'potato', 'de' => 'Kartoffel' ],
				'statements' => [ 'P123' => [ [ 'some' => 'statement' ] ] ],
				'forms' => [ self::VALID_FORM ],
				'senses' => [ self::VALID_SENSE ],
			] )
		) );

		$this->assertEquals(
			new LexemeToCreate(
				new ItemId( self::VALID_LEXEME['language'] ),
				new ItemId( self::VALID_LEXEME['lexical_category'] ),
				new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ),
				$statements,
				forms: [ $form ],
				senses: [ $sense ],
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

	public function testGivenInvalidLemmas_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'lemmas' => [ 'en' => '' ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( '/lexeme/lemmas/en' ), $e );
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

		$this->assertTrue( $validator->getValidatedLexeme()->statements->isEmpty() );
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

	/**
	 * @dataProvider provideFormsNotAList
	 */
	public function testGivenFormsNotAList_throwsUseCaseError( mixed $forms ): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'forms' => $forms ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( '/lexeme/forms' ), $e );
		}
	}

	public static function provideFormsNotAList(): iterable {
		yield 'string' => [ 'potato' ];
		yield 'associative array' => [ [ 'F1' => self::VALID_FORM ] ];
	}

	/**
	 * @dataProvider provideNotAnObject
	 */
	public function testGivenFormNotAnObject_throwsUseCaseError( mixed $form ): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'forms' => [ self::VALID_FORM, $form ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( '/lexeme/forms/1' ), $e );
		}
	}

	public function testGivenFormValidationError_throwsUseCaseError(): void {
		$expectedError = UseCaseError::newMissingField( '/lexeme/forms/0', 'representations' );
		$formValidator = $this->createStub( FormValidator::class );
		$formValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedError );

		try {
			$this->newValidator( formValidator: $formValidator )->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'forms' => [ [] ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	/**
	 * @dataProvider provideSensesNotAList
	 */
	public function testGivenSensesNotAList_throwsUseCaseError( mixed $senses ): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'senses' => $senses ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( '/lexeme/senses' ), $e );
		}
	}

	public static function provideSensesNotAList(): iterable {
		yield 'string' => [ 'potato' ];
		yield 'associative array' => [ [ 'S1' => self::VALID_SENSE ] ];
	}

	/**
	 * @dataProvider provideNotAnObject
	 */
	public function testGivenSenseNotAnObject_throwsUseCaseError( mixed $sense ): void {
		try {
			$this->newValidator()->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'senses' => [ self::VALID_SENSE, $sense ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( '/lexeme/senses/1' ), $e );
		}
	}

	public static function provideNotAnObject(): iterable {
		yield 'string' => [ 'potato' ];
		yield 'null' => [ null ];
		yield 'list' => [ [ 'a starchy tuber' ] ];
	}

	public function testGivenSenseValidationError_throwsUseCaseError(): void {
		$expectedError = UseCaseError::newMissingField( '/lexeme/senses/0', 'glosses' );
		$senseValidator = $this->createStub( SenseValidator::class );
		$senseValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedError );

		try {
			$this->newValidator( senseValidator: $senseValidator )->validateAndDeserialize( self::newRequest(
				array_merge( self::VALID_LEXEME, [ 'senses' => [ [] ] ] )
			) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
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
		?FormValidator $formValidator = null,
		?SenseValidator $senseValidator = null,
	): CreateLexemeValidator {
		if ( $senseValidator === null ) {
			$senseValidator = $this->createStub( SenseValidator::class );
			$senseValidator->method( 'validateAndDeserialize' )->willReturn( new BlankSense() );
		}
		if ( $formValidator === null ) {
			$formValidator = $this->createStub( FormValidator::class );
			$formValidator->method( 'validateAndDeserialize' )->willReturn( new BlankForm() );
		}

		return new CreateLexemeValidator(
			new LexemeTermsValidator(
				new class( self::VALID_LANGUAGE_CODES ) implements LexemeTermLanguageCodeValidator {
					public function __construct( private array $validLanguageCodes ) {
					}

					public function isValid( string $languageCode ): bool {
						return in_array( $languageCode, $this->validLanguageCodes );
					}
				},
				LemmaTermValidator::LEMMA_MAX_LENGTH,
			),
			new ItemIdValidator(
				new class( self::EXISTING_ITEM_IDS ) implements ItemExistenceChecker {
					public function __construct( private array $existingItemIds ) {
					}

					public function exists( ItemId $itemId ): bool {
						return in_array( $itemId->getSerialization(), $this->existingItemIds );
					}
				}
			),
			$statementsValidator ?? $this->newStatementsValidator( new StatementList() ),
			new StatementsValidationErrorConverter(),
			$formValidator,
			$senseValidator,
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

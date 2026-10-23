<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\UseCaseRequestValidation;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermValueValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\FormValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\ItemIdValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Lexeme\Validation\ItemExistenceChecker;
use Wikibase\Lexeme\Validation\LexemeTermLanguageCodeValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\UseCaseRequestValidation\FormValidator
 *
 * @license GPL-2.0-or-later
 */
class FormValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_LANGUAGE_CODES = [ 'en', 'en-gb' ];
	private const EXISTING_ITEM_IDS = [ 'Q1', 'Q2', 'Q123' ];

	private const VALID_FORM = [
		'representations' => [ 'en' => 'potatoes' ],
	];
	private const BASE_PATH = '/lexeme/forms/0';

	public function testGivenValidForm_returnsBlankForm(): void {
		$enRepresentation = 'colors';
		$enGbRepresentation = 'colours';
		$grammaticalFeatureId = new ItemId( 'Q123' );
		$propertyId = new NumericPropertyId( 'P123' );
		$statement = new Statement( new PropertyNoValueSnak( $propertyId ) );

		$validator = $this->newValidator( $this->newStatementsValidator( new StatementList( $statement ) ) );

		$form = $validator->validateAndDeserialize( [
			'representations' => [ 'en' => $enRepresentation, 'en-gb' => $enGbRepresentation ],
			'grammatical_features' => [ $grammaticalFeatureId->getSerialization() ],
			'statements' => [ $propertyId->getSerialization() => [ [ 'some' => 'statement' ] ] ],
		], self::BASE_PATH );

		$this->assertEquals(
			new TermList( [ new Term( 'en', $enRepresentation ), new Term( 'en-gb', $enGbRepresentation ) ] ),
			$form->getRepresentations()
		);
		$this->assertEquals( [ $grammaticalFeatureId ], $form->getGrammaticalFeatures() );
		$this->assertEquals( new StatementList( $statement ), $form->getStatements() );
	}

	public function testGivenMissingRepresentations_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( [], self::BASE_PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( self::BASE_PATH, 'representations' ), $e );
		}
	}

	public function testGivenInvalidRepresentations_throwsUseCaseError(): void {
		try {
			$this->newValidator()
				->validateAndDeserialize( [ 'representations' => [ 'en' => '' ] ], self::BASE_PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( self::BASE_PATH . '/representations/en' ), $e );
		}
	}

	public function testGivenStatementsNotAnArray_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize(
				array_merge( self::VALID_FORM, [ 'statements' => 'potato' ] ), self::BASE_PATH
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => self::BASE_PATH . '/statements' ], $e->context );
		}
	}

	public function testGivenStatementsNull_treatedAsAbsent(): void {
		$validator = $this->newValidator();

		$form = $validator->validateAndDeserialize(
			array_merge( self::VALID_FORM, [ 'statements' => null ] ), self::BASE_PATH
		);

		$this->assertTrue( $form->getStatements()->isEmpty() );
	}

	public function testGivenStatementsValidationError_throwsUseCaseError(): void {
		$statementsValidator = $this->createStub( StatementsValidator::class );
		$statementsValidator->method( 'validateNewStatements' )->willReturn(
			new ValidationError( StatementValidator::CODE_MISSING_FIELD, [
				StatementValidator::CONTEXT_PATH => self::BASE_PATH . '/statements/P123/0',
				StatementValidator::CONTEXT_FIELD => 'value',
			] )
		);

		try {
			$this->newValidator( $statementsValidator )->validateAndDeserialize(
				array_merge( self::VALID_FORM, [ 'statements' => [ 'P123' => [] ] ] ), self::BASE_PATH
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( self::BASE_PATH . '/statements/P123/0', 'value' ), $e );
		}
	}

	public function testGivenGrammaticalFeaturesNotAnArray_throwsUseCaseError(): void {
		try {
			$this->newValidator( $this->createStub( StatementsValidator::class ) )
				->validateAndDeserialize(
					array_merge( self::VALID_FORM, [ 'grammatical_features' => 'Q123' ] ), self::BASE_PATH
				);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( self::BASE_PATH . '/grammatical_features' ), $e );
		}
	}

	public function testGivenGrammaticalFeaturesNotAList_throwsUseCaseError(): void {
		try {
			$this->newValidator( $this->createStub( StatementsValidator::class ) )
				->validateAndDeserialize(
					array_merge(
						self::VALID_FORM,
						[ 'grammatical_features' => [ 'foo' => 'Q123' ] ]
					),
					self::BASE_PATH
				);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( self::BASE_PATH . '/grammatical_features' ), $e );
		}
	}

	/**
	 * @dataProvider provideInvalidGrammaticalFeature
	 */
	public function testGivenGrammaticalFeatureInvalidItemId_throwsUseCaseError(
		array $grammaticalFeatures,
		int $index
	): void {
		try {
			$this->newValidator( $this->createStub( StatementsValidator::class ) )->validateAndDeserialize(
				array_merge( self::VALID_FORM, [ 'grammatical_features' => $grammaticalFeatures ] ), self::BASE_PATH
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals(
				UseCaseError::newInvalidValue( self::BASE_PATH . "/grammatical_features/$index" ),
				$e
			);
		}
	}

	public static function provideInvalidGrammaticalFeature(): iterable {
		yield "int" => [ [ 42 ], 0 ];
		yield "null" => [ [ null ], 0 ];
		yield "array" => [ [ [ 'Q1' ] ], 0 ];
		yield "empty string" => [ [ '' ], 0 ];
		yield "not an id" => [ [ 'potato' ], 0 ];
		yield "property id" => [ [ 'P123' ], 0 ];
		yield "lexeme id" => [ [ 'L1' ], 0 ];
		yield "second element invalid" => [ [ 'Q1', 'P2' ], 1 ];
	}

	public function testGivenNonexistentGrammaticalFeature_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize(
				array_merge( self::VALID_FORM, [ 'grammatical_features' => [ 'Q999' ] ] ), self::BASE_PATH
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::REFERENCED_RESOURCE_NOT_FOUND, $e->errorCode );
			$this->assertSame( 'The referenced resource does not exist', $e->errorMessage );
			$this->assertSame(
				[ UseCaseError::CONTEXT_PATH => self::BASE_PATH . '/grammatical_features/0' ],
				$e->context
			);
		}
	}

	private function newValidator(
		?StatementsValidator $statementsValidator = null,
	): FormValidator {
		return new FormValidator(
			new LexemeTermsValidator(
				new class( self::VALID_LANGUAGE_CODES ) implements LexemeTermLanguageCodeValidator {
					public function __construct( private array $validLanguageCodes ) {
					}

					public function isValid( string $languageCode ): bool {
						return in_array( $languageCode, $this->validLanguageCodes );
					}
				},
				LexemeTermValueValidator::MAX_LENGTH,
			),
			$statementsValidator ?? $this->newStatementsValidator( new StatementList() ),
			new StatementsValidationErrorConverter(),
			new ItemIdValidator(
				new class( self::EXISTING_ITEM_IDS ) implements ItemExistenceChecker {
					public function __construct( private array $existingItemIds ) {
					}

					public function exists( ItemId $itemId ): bool {
						return in_array( $itemId->getSerialization(), $this->existingItemIds );
					}
				}
			),
		);
	}

	private function newStatementsValidator( StatementList $validatedStatements ): StatementsValidator {
		$statementsValidator = $this->createStub( StatementsValidator::class );
		$statementsValidator->method( 'getValidatedStatements' )->willReturn( $validatedStatements );

		return $statementsValidator;
	}

}

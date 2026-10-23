<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\UseCaseRequestValidation;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LexemeTermValueValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\SenseValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Lexeme\Validation\LexemeTermLanguageCodeValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\UseCaseRequestValidation\SenseValidator
 *
 * @license GPL-2.0-or-later
 */
class SenseValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_LANGUAGE_CODES = [ 'en', 'en-gb' ];
	private const string BASE_PATH = '/lexeme/senses/0';
	private const array VALID_SENSE = [
		'glosses' => [ 'en' => 'a starchy tuber' ],
	];

	public function testGivenValidSense_returnsBlankSense(): void {
		$enGloss = 'visual perception of light wavelengths';
		$propertyId = new NumericPropertyId( 'P123' );
		$statement = new Statement( new PropertyNoValueSnak( $propertyId ) );

		$sense = $this->newValidator( $this->newStatementsValidator( new StatementList( $statement ) ) )
			->validateAndDeserialize(
				[
					'glosses' => [ 'en' => $enGloss ],
					'statements' => [ $propertyId->getSerialization() => [ [ 'some' => 'statement' ] ] ],
				],
				self::BASE_PATH
			);

		$this->assertEquals( new TermList( [ new Term( 'en', $enGloss ) ] ), $sense->getGlosses() );
		$this->assertEquals( new StatementList( $statement ), $sense->getStatements() );
	}

	public function testGivenMissingGlosses_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( [], self::BASE_PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( self::BASE_PATH, 'glosses' ), $e );
		}
	}

	public function testGivenInvalidGlosses_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize( [ 'glosses' => [ 'en' => '' ] ], self::BASE_PATH );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( self::BASE_PATH . '/glosses/en' ), $e );
		}
	}

	public function testGivenStatementsNotAnArray_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize(
				array_merge( self::VALID_SENSE, [ 'statements' => 'potato' ] ),
				self::BASE_PATH
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( self::BASE_PATH . '/statements' ), $e );
		}
	}

	public function testGivenStatementsNull_treatedAsAbsent(): void {
		$sense = $this->newValidator()->validateAndDeserialize(
			array_merge( self::VALID_SENSE, [ 'statements' => null ] ),
			self::BASE_PATH
		);

		$this->assertTrue( $sense->getStatements()->isEmpty() );
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
				array_merge( self::VALID_SENSE, [ 'statements' => [ 'P123' => [] ] ] ),
				self::BASE_PATH
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals(
				UseCaseError::newMissingField( self::BASE_PATH . '/statements/P123/0', 'value' ),
				$e
			);
		}
	}

	private function newValidator( ?StatementsValidator $statementsValidator = null ): SenseValidator {
		return new SenseValidator(
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
		);
	}

	private function newStatementsValidator( StatementList $validatedStatements ): StatementsValidator {
		$statementsValidator = $this->createStub( StatementsValidator::class );
		$statementsValidator->method( 'getValidatedStatements' )->willReturn( $validatedStatements );

		return $statementsValidator;
	}

}

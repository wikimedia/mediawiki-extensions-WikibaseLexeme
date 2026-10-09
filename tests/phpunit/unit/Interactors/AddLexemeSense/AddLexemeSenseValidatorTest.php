<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeSense;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseRequest;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Lexeme\Validation\LexemeTermLanguageCodeValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseValidator
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_LANGUAGE_CODES = [ 'en', 'en-gb' ];
	private const array VALID_SENSE = [
		'glosses' => [ 'en' => 'a starchy tuber' ],
	];

	public function testGivenValidRequest_exposesLexemeIdAndSense(): void {
		$enGloss = 'visual perception of light wavelengths';
		$propertyId = new NumericPropertyId( 'P123' );
		$statement = new Statement( new PropertyNoValueSnak( $propertyId ) );

		$validator = $this->newValidator( $this->newStatementsValidator( new StatementList( $statement ) ) );

		$validator->validate( $this->newRequest( [
			'glosses' => [ 'en' => $enGloss ],
			'statements' => [ $propertyId->getSerialization() => [ [ 'some' => 'statement' ] ] ],
		] ) );

		$this->assertEquals( new LexemeId( 'L1' ), $validator->getValidatedLexemeId() );
		$sense = $validator->getValidatedSense();
		$this->assertEquals(
			new TermList( [ new Term( 'en', $enGloss ) ] ),
			$sense->getGlosses()
		);
		$this->assertEquals( new StatementList( $statement ), $sense->getStatements() );
	}

	public function testGivenValidRequest_validatesEditMetadata(): void {
		$editTags = [ 'allowed tag' ];
		$comment = 'user comment';
		$editMetadataRequestValidator = $this->createMock( EditMetadataRequestValidator::class );
		$editMetadataRequestValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $editTags, $comment );

		$this->newValidator( editMetadataRequestValidator: $editMetadataRequestValidator )->validate(
			new AddLexemeSenseRequest( 'L1', [ 'glosses' => [ 'en' => 'gloss' ] ], $editTags, false, $comment )
		);
	}

	public function testGivenInvalidLexemeId_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate(
				new AddLexemeSenseRequest( 'not-a-lexeme-id', [ 'glosses' => [ 'en' => 'gloss' ] ], [], false, null )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ], $e->context );
		}
	}

	public function testGivenMissingGlosses_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate( $this->newRequest( [] ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( '/sense', 'glosses' ), $e );
		}
	}

	public function testGivenInvalidGlosses_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate( $this->newRequest( [ 'glosses' => [ 'en' => '' ] ] ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newInvalidValue( '/sense/glosses/en' ), $e );
		}
	}

	public function testGivenValidateNotCalled_getValidatedLexemeIdThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedLexemeId();
	}

	public function testGivenValidateNotCalled_getValidatedSenseThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedSense();
	}

	public function testGivenStatementsNotAnArray_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate(
				$this->newRequest( array_merge( self::VALID_SENSE, [ 'statements' => 'potato' ] ) )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/sense/statements' ], $e->context );
		}
	}

	public function testGivenStatementsNull_treatedAsAbsent(): void {
		$validator = $this->newValidator();

		$validator->validate(
			$this->newRequest( array_merge( self::VALID_SENSE, [ 'statements' => null ] ) )
		);

		$this->assertTrue( $validator->getValidatedSense()->getStatements()->isEmpty() );
	}

	public function testGivenStatementsValidationError_throwsUseCaseError(): void {
		$statementsValidator = $this->createStub( StatementsValidator::class );
		$statementsValidator->method( 'validateNewStatements' )->willReturn(
			new ValidationError( StatementValidator::CODE_MISSING_FIELD, [
				StatementValidator::CONTEXT_PATH => '/sense/statements/P123/0',
				StatementValidator::CONTEXT_FIELD => 'value',
			] )
		);

		try {
			$this->newValidator( $statementsValidator )->validate(
				$this->newRequest( array_merge( self::VALID_SENSE, [ 'statements' => [ 'P123' => [] ] ] ) )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( '/sense/statements/P123/0', 'value' ), $e );
		}
	}

	private function newRequest( array $sense ): AddLexemeSenseRequest {
		return new AddLexemeSenseRequest( 'L1', $sense, [], false, null );
	}

	private function newValidator(
		?StatementsValidator $statementsValidator = null,
		?EditMetadataRequestValidator $editMetadataRequestValidator = null,
	): AddLexemeSenseValidator {
		return new AddLexemeSenseValidator(
			new LexemeIdValidator(),
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
			$statementsValidator ?? $this->newStatementsValidator( new StatementList() ),
			new StatementsValidationErrorConverter(),
			$editMetadataRequestValidator ?? $this->createStub( EditMetadataRequestValidator::class ),
		);
	}

	private function newStatementsValidator( StatementList $validatedStatements ): StatementsValidator {
		$statementsValidator = $this->createStub( StatementsValidator::class );
		$statementsValidator->method( 'getValidatedStatements' )->willReturn( $validatedStatements );

		return $statementsValidator;
	}

}

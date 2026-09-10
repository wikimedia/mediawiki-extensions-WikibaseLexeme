<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeStatement;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementRequest;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementValidationErrorConverter;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementValidator
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_STATEMENT = [
		'property' => [ 'id' => 'P123' ],
		'value' => [ 'type' => 'novalue' ],
	];

	public function testGivenValidRequest_exposesLexemeIdAndStatement(): void {
		$statement = new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) );
		$validator = $this->newValidator( $this->newStatementValidator( $statement ) );

		$validator->validate( self::newRequest( [ 'allowed tag' ], 'user comment' ) );

		$this->assertEquals( new LexemeId( 'L1' ), $validator->getValidatedLexemeId() );
		$this->assertSame( $statement, $validator->getValidatedStatement() );
	}

	public function testGivenValidRequest_validatesEditMetadata(): void {
		$editTags = [ 'allowed tag' ];
		$comment = 'user comment';
		$editMetadataRequestValidator = $this->createMock( EditMetadataRequestValidator::class );
		$editMetadataRequestValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $editTags, $comment );

		$this->newValidator( null, $editMetadataRequestValidator )
			->validate( self::newRequest( $editTags, $comment ) );
	}

	public function testGivenInvalidLexemeId_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate(
				new AddLexemeStatementRequest( 'not-a-lexeme-id', self::VALID_STATEMENT, [], false, null )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ], $e->context );
		}
	}

	public function testGivenInvalidStatement_throwsUseCaseError(): void {
		$statementValidator = $this->createStub( StatementValidator::class );
		$statementValidator->method( 'validate' )->willReturn(
			new ValidationError( StatementValidator::CODE_MISSING_FIELD, [
				StatementValidator::CONTEXT_PATH => '/statement',
				StatementValidator::CONTEXT_FIELD => 'value',
			] )
		);

		try {
			$this->newValidator( $statementValidator )->validate( self::newRequest( [], null ) );
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( '/statement', 'value' ), $e );
		}
	}

	public function testGivenValidateNotCalled_getValidatedLexemeIdThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedLexemeId();
	}

	public function testGivenValidateNotCalled_getValidatedStatementThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedStatement();
	}

	private static function newRequest( array $editTags, ?string $comment ): AddLexemeStatementRequest {
		return new AddLexemeStatementRequest( 'L1', self::VALID_STATEMENT, $editTags, false, $comment );
	}

	private function newValidator(
		?StatementValidator $statementValidator = null,
		?EditMetadataRequestValidator $editMetadataRequestValidator = null,
	): AddLexemeStatementValidator {
		return new AddLexemeStatementValidator(
			new LexemeIdValidator(),
			$statementValidator ?? $this->newStatementValidator(
				new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ) )
			),
			new StatementValidationErrorConverter(),
			$editMetadataRequestValidator ?? $this->createStub( EditMetadataRequestValidator::class ),
		);
	}

	private function newStatementValidator( Statement $validatedStatement ): StatementValidator {
		$statementValidator = $this->createStub( StatementValidator::class );
		$statementValidator->method( 'getValidatedStatement' )->willReturn( $validatedStatement );

		return $statementValidator;
	}

}

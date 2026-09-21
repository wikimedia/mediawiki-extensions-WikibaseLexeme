<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\UseCaseRequestValidation;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter
 *
 * @license GPL-2.0-or-later
 */
class StatementsValidationErrorConverterTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideValidationError
	 */
	public function testConvertsToUseCaseError( ValidationError $validationError, UseCaseError $expectedError ): void {
		$this->assertEquals(
			$expectedError,
			( new StatementsValidationErrorConverter() )->toUseCaseError( $validationError )
		);
	}

	public static function provideValidationError(): iterable {
		yield 'statements not associative' => [
			new ValidationError( StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE, [
				StatementsValidator::CONTEXT_PATH => '/statements',
				StatementsValidator::CONTEXT_VALUE => [ 'potato' ],
			] ),
			UseCaseError::newInvalidValue( '/statements' ),
		];

		yield 'statement group not sequential' => [
			new ValidationError( StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL, [
				StatementsValidator::CONTEXT_PATH => '/statements/P123',
				StatementsValidator::CONTEXT_VALUE => [ 'potato' => 'tomato' ],
			] ),
			UseCaseError::newInvalidValue( '/statements/P123' ),
		];

		yield 'statement not an array' => [
			new ValidationError( StatementsValidator::CODE_STATEMENT_NOT_ARRAY, [
				StatementsValidator::CONTEXT_PATH => '/statements/P123/0',
				StatementsValidator::CONTEXT_VALUE => 'potato',
			] ),
			UseCaseError::newInvalidValue( '/statements/P123/0' ),
		];

		yield 'property id mismatch' => [
			new ValidationError( StatementsValidator::CODE_PROPERTY_ID_MISMATCH, [
				StatementsValidator::CONTEXT_PATH => '/statements/P123/0/property/id',
				StatementsValidator::CONTEXT_PROPERTY_ID_KEY => 'P123',
				StatementsValidator::CONTEXT_PROPERTY_ID_VALUE => 'P321',
			] ),
			UseCaseError::newStatementGroupPropertyIdMismatch(
				'/statements/P123/0/property/id',
				'P123',
				'P321',
			),
		];

		yield 'invalid statement field' => [
			new ValidationError( StatementValidator::CODE_INVALID_FIELD, [
				StatementValidator::CONTEXT_FIELD => 'rank',
				StatementValidator::CONTEXT_VALUE => 'potato',
				StatementValidator::CONTEXT_PATH => '/statement/rank',
			] ),
			UseCaseError::newInvalidValue( '/statement/rank' ),
		];

		yield 'invalid statement field type' => [
			new ValidationError( StatementValidator::CODE_INVALID_FIELD_TYPE, [
				StatementValidator::CONTEXT_PATH => '/statement/qualifiers',
				StatementValidator::CONTEXT_VALUE => 'potato',
			] ),
			UseCaseError::newInvalidValue( '/statement/qualifiers' ),
		];

		yield 'missing statement field' => [
			new ValidationError( StatementValidator::CODE_MISSING_FIELD, [
				StatementValidator::CONTEXT_PATH => '/statement',
				StatementValidator::CONTEXT_FIELD => 'value',
			] ),
			UseCaseError::newMissingField( '/statement', 'value' ),
		];

		yield 'property not found' => [
			new ValidationError( StatementValidator::CODE_PROPERTY_NOT_FOUND, [
				StatementValidator::CONTEXT_PATH => '/statement/property/id',
			] ),
			UseCaseError::newReferencedResourceNotFound( '/statement/property/id' ),
		];
	}

	public function testGivenUnknownErrorCode_throwsLogicException(): void {
		$this->expectException( LogicException::class );

		( new StatementsValidationErrorConverter() )->toUseCaseError( new ValidationError( 'unknown-error-code' ) );
	}

}

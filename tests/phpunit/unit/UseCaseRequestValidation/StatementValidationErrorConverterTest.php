<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\UseCaseRequestValidation;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementValidationErrorConverter;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\UseCaseRequestValidation\StatementValidationErrorConverter
 *
 * @license GPL-2.0-or-later
 */
class StatementValidationErrorConverterTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideValidationError
	 */
	public function testConvertsToUseCaseError( ValidationError $validationError, UseCaseError $expectedError ): void {
		$this->assertEquals(
			$expectedError,
			( new StatementValidationErrorConverter() )->toUseCaseError( $validationError )
		);
	}

	public static function provideValidationError(): iterable {
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

		( new StatementValidationErrorConverter() )->toUseCaseError( new ValidationError( 'unknown-error-code' ) );
	}

}

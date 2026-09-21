<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeForm;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormRequest;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormValidator
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeFormValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_FORM = [
		'representations' => [ 'en' => 'potatoes' ],
	];

	public function testGivenValidRequest_exposesForm(): void {
		$enRepresentation = 'colors';
		$enGbRepresentation = 'colours';
		$grammaticalFeatureId = new ItemId( 'Q123' );
		$propertyId = new NumericPropertyId( 'P123' );
		$statement = new Statement( new PropertyNoValueSnak( $propertyId ) );

		$validator = $this->newValidator( $this->newStatementsValidator( new StatementList( $statement ) ) );

		$validator->validate( $this->newRequest( [
			'representations' => [ 'en' => $enRepresentation, 'en-gb' => $enGbRepresentation ],
			'grammatical_features' => [ $grammaticalFeatureId->getSerialization() ],
			'statements' => [ $propertyId->getSerialization() => [ [ 'some' => 'statement' ] ] ],
		] ) );

		$form = $validator->getValidatedForm();
		$this->assertEquals(
			new TermList( [ new Term( 'en', $enRepresentation ), new Term( 'en-gb', $enGbRepresentation ) ] ),
			$form->getRepresentations()
		);
		$this->assertEquals( [ $grammaticalFeatureId ], $form->getGrammaticalFeatures() );
		$this->assertEquals( new StatementList( $statement ), $form->getStatements() );
	}

	public function testGivenStatementsNotAnArray_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validate(
				$this->newRequest( array_merge( self::VALID_FORM, [ 'statements' => 'potato' ] ) )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_VALUE, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PATH => '/form/statements' ], $e->context );
		}
	}

	public function testGivenStatementsNull_treatedAsAbsent(): void {
		$validator = $this->newValidator();

		$validator->validate(
			$this->newRequest( array_merge( self::VALID_FORM, [ 'statements' => null ] ) )
		);

		$this->assertTrue( $validator->getValidatedForm()->getStatements()->isEmpty() );
	}

	public function testGivenStatementsValidationError_throwsUseCaseError(): void {
		$statementsValidator = $this->createStub( StatementsValidator::class );
		$statementsValidator->method( 'validateNewStatements' )->willReturn(
			new ValidationError( StatementValidator::CODE_MISSING_FIELD, [
				StatementValidator::CONTEXT_PATH => '/form/statements/P123/0',
				StatementValidator::CONTEXT_FIELD => 'value',
			] )
		);

		try {
			$this->newValidator( $statementsValidator )->validate(
				$this->newRequest( array_merge( self::VALID_FORM, [ 'statements' => [ 'P123' => [] ] ] ) )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::newMissingField( '/form/statements/P123/0', 'value' ), $e );
		}
	}

	public function testGivenValidateAndDeserializeNotCalled_getValidatedFormThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedForm();
	}

	private function newRequest( array $form ): AddLexemeFormRequest {
		return new AddLexemeFormRequest( 'L1', $form, [], false, null );
	}

	private function newValidator( ?StatementsValidator $statementsValidator = null ): AddLexemeFormValidator {
		return new AddLexemeFormValidator(
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

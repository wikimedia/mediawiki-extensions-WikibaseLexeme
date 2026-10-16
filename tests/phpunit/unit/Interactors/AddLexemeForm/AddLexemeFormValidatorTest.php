<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\Interactors\AddLexemeForm;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormRequest;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormValidator;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\FormValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;

/**
 * @covers \Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormValidator
 *
 * @license GPL-2.0-or-later
 */
class AddLexemeFormValidatorTest extends MediaWikiUnitTestCase {

	private const VALID_FORM = [
		'representations' => [ 'en' => 'potatoes' ],
	];

	public function testGivenValidRequest_exposesLexemeIdAndForm(): void {
		$form = new BlankForm();
		$formValidator = $this->createMock( FormValidator::class );

		$formValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( self::VALID_FORM, '/form' )
			->willReturn( $form );

		$validator = $this->newValidator( $formValidator );
		$validator->validateAndDeserialize( new AddLexemeFormRequest( 'L1', self::VALID_FORM, [], false, null, null ) );

		$this->assertEquals( new LexemeId( 'L1' ), $validator->getValidatedLexemeId() );
		$this->assertSame( $form, $validator->getValidatedForm() );
	}

	public function testGivenValidRequest_validatesEditMetadata(): void {
		$editTags = [ 'allowed tag' ];
		$comment = 'user comment';
		$editMetadataRequestValidator = $this->createMock( EditMetadataRequestValidator::class );
		$editMetadataRequestValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $editTags, $comment );

		$this->newValidator( null, $editMetadataRequestValidator )->validateAndDeserialize(
			new AddLexemeFormRequest( 'L1', self::VALID_FORM, $editTags, false, $comment, null )
		);
	}

	public function testGivenInvalidLexemeId_throwsUseCaseError(): void {
		try {
			$this->newValidator()->validateAndDeserialize(
				new AddLexemeFormRequest( 'not-a-lexeme-id', self::VALID_FORM, [], false, null, null )
			);
			$this->fail( 'Expected UseCaseError to be thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->errorCode );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'lexeme_id' ], $e->context );
		}
	}

	public function testGivenValidateAndDeserializeNotCalled_getValidatedLexemeIdThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedLexemeId();
	}

	public function testGivenValidateAndDeserializeNotCalled_getValidatedFormThrows(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedForm();
	}

	private function newValidator(
		?FormValidator $formValidator = null,
		?EditMetadataRequestValidator $editMetadataRequestValidator = null,
	): AddLexemeFormValidator {
		if ( $formValidator === null ) {
			$formValidator = $this->createStub( FormValidator::class );
			$formValidator->method( 'validateAndDeserialize' )
				->willReturn( new BlankForm() );
		}

		return new AddLexemeFormValidator(
			new LexemeIdValidator(),
			$formValidator,
			$editMetadataRequestValidator ?? $this->createStub( EditMetadataRequestValidator::class ),
		);
	}
}

<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeForm;

use LogicException;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\FormValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeFormValidator {

	private ?LexemeId $lexemeId = null;
	private ?BlankForm $form = null;

	public function __construct(
		private LexemeIdValidator $lexemeIdValidator,
		private FormValidator $formValidator,
		private EditMetadataRequestValidator $editMetadataRequestValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddLexemeFormRequest $request ): void {
		$this->lexemeId = $this->lexemeIdValidator->validate( $request->lexemeId );

		$this->form = $this->formValidator->validateAndDeserialize( $request->form, '/form' );

		$this->editMetadataRequestValidator->validate( $request->editTags, $request->comment );
	}

	public function getValidatedLexemeId(): LexemeId {
		if ( $this->lexemeId === null ) {
			throw new LogicException( 'Must not call getValidatedLexemeId() before validateAndDeserialize()' );
		}

		return $this->lexemeId;
	}

	public function getValidatedForm(): BlankForm {
		if ( $this->form === null ) {
			throw new LogicException( 'Must not call getValidatedForm() before validateAndDeserialize()' );
		}

		return $this->form;
	}
}

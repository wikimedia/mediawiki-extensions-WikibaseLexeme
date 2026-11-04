<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\PatchLexeme;

use LogicException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;
use Wikibase\Lexeme\Validation\JsonPatchValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchLexemeValidator {

	private ?LexemeId $lexemeId = null;

	public function __construct(
		private LexemeIdValidator $lexemeIdValidator,
		private JsonPatchValidator $jsonPatchValidator,
		private EditMetadataRequestValidator $editMetadataRequestValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( PatchLexemeRequest $request ): void {
		$this->lexemeId = $this->lexemeIdValidator->validate( $request->lexemeId );
		$this->validatePatch( $request->patch );
		$this->editMetadataRequestValidator->validate( $request->editTags, $request->comment );
	}

	public function getValidatedLexemeId(): LexemeId {
		if ( $this->lexemeId === null ) {
			throw new LogicException( 'Must not call getValidatedLexemeId() before validate()' );
		}

		return $this->lexemeId;
	}

	/**
	 * @throws UseCaseError
	 */
	private function validatePatch( array $patch ): void {
		$validationError = $this->jsonPatchValidator->validate( $patch );
		if ( !$validationError ) {
			return;
		}

		$context = $validationError->getContext();

		throw match ( $validationError->getCode() ) {
			JsonPatchValidator::CODE_INVALID => UseCaseError::newInvalidValue( '/patch' ),
			JsonPatchValidator::CODE_INVALID_OPERATION => UseCaseError::newInvalidValue(
				$this->getOperationPath( $context, $patch ) . '/op',
			),
			JsonPatchValidator::CODE_INVALID_FIELD_TYPE => UseCaseError::newInvalidValue(
				$this->getOperationPath( $context, $patch ) . '/' . $context[JsonPatchValidator::CONTEXT_FIELD],
			),
			JsonPatchValidator::CODE_MISSING_FIELD => UseCaseError::newMissingField(
				$this->getOperationPath( $context, $patch ),
				$context[JsonPatchValidator::CONTEXT_FIELD],
			),
		};
	}

	private function getOperationPath( array $errorContext, array $patch ): string {
		$index = array_search( $errorContext[JsonPatchValidator::CONTEXT_OPERATION], $patch );
		if ( !is_int( $index ) ) {
			throw new LogicException( 'Could not find operation in patch' );
		}

		return "/patch/$index";
	}

}

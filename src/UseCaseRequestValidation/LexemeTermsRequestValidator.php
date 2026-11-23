<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\UseCaseRequestValidation;

use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\LexemeTermsValidator;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermsRequestValidator {

	public function __construct(
		private LexemeTermsValidator $lexemeTermsValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( mixed $terms, string $path ): TermList {
		$validationError = $this->lexemeTermsValidator->validate( $terms, $path );
		if ( $validationError ) {
			throw match ( $validationError->code ) {
				LexemeTermsValidator::CODE_INVALID_KEY => UseCaseError::newInvalidKey(
					$validationError->context[ LexemeTermsValidator::CONTEXT_PATH ],
					$validationError->context[ LexemeTermsValidator::CONTEXT_KEY ]
				),
				LexemeTermsValidator::CODE_VALUE_TOO_LONG => UseCaseError::newValueTooLong(
					$validationError->context[ LexemeTermsValidator::CONTEXT_PATH ],
					$validationError->context[ LexemeTermsValidator::CONTEXT_LIMIT ]
				),
				LexemeTermsValidator::CODE_INVALID_VALUE => UseCaseError::newInvalidValue(
					$validationError->context[ LexemeTermsValidator::CONTEXT_PATH ]
				),
			};
		}
		return $this->lexemeTermsValidator->getValidatedTerms();
	}
}

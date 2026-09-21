<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\UseCaseRequestValidation;

use LogicException;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Statements\Application\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class StatementsValidationErrorConverter {

	public function toUseCaseError( ValidationError $validationError ): UseCaseError {
		$context = $validationError->getContext();
		return match ( $validationError->getCode() ) {
			StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE,
			StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL,
			StatementsValidator::CODE_STATEMENT_NOT_ARRAY =>
				UseCaseError::newInvalidValue( $context[StatementsValidator::CONTEXT_PATH] ),
			StatementsValidator::CODE_PROPERTY_ID_MISMATCH => UseCaseError::newStatementGroupPropertyIdMismatch(
				$context[StatementsValidator::CONTEXT_PATH],
				$context[StatementsValidator::CONTEXT_PROPERTY_ID_KEY],
				$context[StatementsValidator::CONTEXT_PROPERTY_ID_VALUE],
			),
			StatementValidator::CODE_INVALID_FIELD,
			StatementValidator::CODE_INVALID_FIELD_TYPE =>
				UseCaseError::newInvalidValue( $context[StatementValidator::CONTEXT_PATH] ),
			StatementValidator::CODE_MISSING_FIELD => UseCaseError::newMissingField(
				$context[StatementValidator::CONTEXT_PATH],
				$context[StatementValidator::CONTEXT_FIELD],
			),
			StatementValidator::CODE_PROPERTY_NOT_FOUND =>
				UseCaseError::newReferencedResourceNotFound( $context[StatementValidator::CONTEXT_PATH] ),
			default => throw new LogicException( "Unexpected validation error code: {$validationError->getCode()}" ),
		};
	}

}

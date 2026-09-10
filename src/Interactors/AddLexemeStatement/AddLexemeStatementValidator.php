<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeStatement;

use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementValidator {

	public function __construct(
		private EditMetadataRequestValidator $editMetadataRequestValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( AddLexemeStatementRequest $request ): void {
		$this->editMetadataRequestValidator->validate( $request->editTags, $request->comment );
	}

}

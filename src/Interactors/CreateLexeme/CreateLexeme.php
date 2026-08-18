<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexeme {

	public function __construct(
		private LexemeCreator $lexemeCreator,
		private CreateLexemeValidator $validator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( CreateLexemeRequest $request ): CreateLexemeResponse {
		$this->validator->validateAndDeserialize( $request );

		$lexemeRevision = $this->lexemeCreator->create(
			$this->validator->getValidatedLexeme(),
			new EditMetadata( EditSummaryAction::CREATE_LEXEME ),
		);

		return new CreateLexemeResponse(
			$lexemeRevision->lexeme,
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}
}

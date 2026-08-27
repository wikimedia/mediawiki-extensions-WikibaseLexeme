<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;
use Wikibase\Lexeme\Interactors\AssertUserIsAuthorized;
use Wikibase\Lexeme\Interactors\UpdateExceptionHandler;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexeme {

	use UpdateExceptionHandler;

	public function __construct(
		private LexemeCreator $lexemeCreator,
		private CreateLexemeValidator $validator,
		private AssertUserIsAuthorized $assertUserIsAuthorized,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( CreateLexemeRequest $request ): CreateLexemeResponse {
		$this->validator->validateAndDeserialize( $request );
		$this->assertUserIsAuthorized->checkCreateLexemePermissions(
			$request->username === null ? User::newAnonymous() : User::withUsername( $request->username )
		);

		$lexemeRevision = $this->executeWithExceptionHandling( fn () => $this->lexemeCreator->create(
				$this->validator->getValidatedLexeme(),
				$this->validator->getValidatedEditMetadata(),
			)
		);

		return new CreateLexemeResponse(
			$lexemeRevision->lexeme,
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}
}

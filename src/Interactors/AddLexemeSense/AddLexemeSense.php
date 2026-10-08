<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeSense;

use Wikibase\Lexeme\Domain\Model\AddSenseEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeSense {

	public function __construct(
		private LexemeWriteModelRetriever $lexemeRetriever,
		private LexemeUpdater $lexemeUpdater,
		private AddLexemeSenseValidator $validator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( AddLexemeSenseRequest $request ): AddLexemeSenseResponse {
		$this->validator->validate( $request );
		$sense = $this->validator->getValidatedSense();
		$lexemeId = new LexemeId( $request->lexemeId );

		$lexeme = $this->lexemeRetriever->getLexemeWriteModel( $lexemeId );
		$lexeme->addOrUpdateSense( $sense );

		$lexemeRevision = $this->lexemeUpdater->update(
			$lexeme, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$request->editTags,
				$request->isBot,
				new AddSenseEditSummary( $request->comment ),
			),
		);

		return new AddLexemeSenseResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable the Sense was just added
			$lexemeRevision->lexeme->senses->getById( $sense->getId() ),
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}

}

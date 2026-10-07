<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeSense;

use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\AddSenseEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeSense {

	public function __construct(
		private LexemeWriteModelRetriever $lexemeRetriever,
		private LexemeUpdater $lexemeUpdater,
	) {
	}

	public function execute( AddLexemeSenseRequest $request ): AddLexemeSenseResponse {
		$lexemeId = new LexemeId( $request->lexemeId );

		$sense = new BlankSense();
		foreach ( $request->sense['glosses'] as $languageCode => $text ) {
			$sense->getGlosses()->setTextForLanguage( (string)$languageCode, $text );
		}

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

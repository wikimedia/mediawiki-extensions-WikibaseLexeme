<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeForm;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\AddFormEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeForm {

	public function __construct(
		private LexemeWriteModelRetriever $lexemeRetriever,
		private LexemeUpdater $lexemeUpdater,
	) {
	}

	public function execute( AddLexemeFormRequest $request ): AddLexemeFormResponse {
		$lexemeId = new LexemeId( $request->lexemeId );

		$form = new BlankForm();
		$form->setRepresentations( $this->deserializeRepresentations( $request->form['representations'] ) );
		$form->setGrammaticalFeatures( array_map(
			static fn ( string $itemId ) => new ItemId( $itemId ),
			$request->form['grammatical_features'] ?? [],
		) );

		$lexeme = $this->lexemeRetriever->getLexemeWriteModel( $lexemeId );
		$lexeme->addOrUpdateForm( $form );

		$lexemeRevision = $this->lexemeUpdater->update(
			$lexeme, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$request->editTags,
				$request->isBot,
				new AddFormEditSummary( $request->comment ),
			),
		);

		return new AddLexemeFormResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable the Form was just added
			$lexemeRevision->lexeme->forms->getById( $form->getId() ),
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}

	private function deserializeRepresentations( array $representations ): TermList {
		$terms = [];
		foreach ( $representations as $languageCode => $text ) {
			$terms[] = new Term( (string)$languageCode, $text );
		}

		return new TermList( $terms );
	}

}

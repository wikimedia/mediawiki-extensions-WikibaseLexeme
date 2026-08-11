<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexeme {

	public function __construct(
		private LexemeCreator $lexemeCreator,
	) {
	}

	public function execute( CreateLexemeRequest $request ): CreateLexemeResponse {
		$serialization = $request->lexeme;

		$lemmas = new TermList(); // validation + proper deserialization happens in T434436
		foreach ( $serialization['lemmas'] as $languageCode => $text ) {
			$lemmas->setTextForLanguage( $languageCode, $text );
		}

		return new CreateLexemeResponse( $this->lexemeCreator->create( new LexemeWriteModel(
			null,
			$lemmas,
			new ItemId( $serialization['lexical_category'] ),
			new ItemId( $serialization['language'] ),
		) ) );
	}
}

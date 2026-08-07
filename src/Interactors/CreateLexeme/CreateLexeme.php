<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexeme {

	public function execute( CreateLexemeRequest $request ): CreateLexemeResponse {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new Lemmas(),
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new StatementList(),
			new Forms(),
			new Senses(),
		);

		return new CreateLexemeResponse( $lexeme );
	}
}

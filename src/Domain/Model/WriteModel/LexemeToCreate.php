<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\WriteModel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;

/**
 * The parts of a Lexeme that is yet to be created. Unlike a {@link \Wikibase\Lexeme\Domain\Model\Lexeme},
 * it can hold new Forms and Senses without an ID.
 *
 * @license GPL-2.0-or-later
 */
class LexemeToCreate {

	/**
	 * @param ItemId $language
	 * @param ItemId $lexicalCategory
	 * @param TermList $lemmas
	 * @param StatementList $statements
	 * @param BlankForm[] $forms
	 * @param BlankSense[] $senses
	 */
	public function __construct(
		public readonly ItemId $language,
		public readonly ItemId $lexicalCategory,
		public readonly TermList $lemmas,
		public readonly StatementList $statements = new StatementList(),
		public readonly array $forms = [],
		public readonly array $senses = [],
	) {
	}

}

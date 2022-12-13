<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
interface LemmaLookup {
	public function getLemmas( LexemeId $lexemeId ): TermList;
}

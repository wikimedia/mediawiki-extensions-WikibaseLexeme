<?php

namespace Wikibase\Lexeme\Domain\Storage;

use Wikibase\Lexeme\Domain\Model\Lexeme;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeRepository {

	/**
	 * @throws UpdateLexemeException
	 */
	public function updateLexeme( Lexeme $lexeme, /* string */ $editSummary );

}

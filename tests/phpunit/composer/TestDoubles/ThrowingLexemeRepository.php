<?php

namespace Wikibase\Lexeme\Tests\TestDoubles;

use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Storage\LexemeRepository;
use Wikibase\Lexeme\Domain\Storage\UpdateLexemeException;

/**
 * @license GPL-2.0-or-later
 */
class ThrowingLexemeRepository implements LexemeRepository {

	/**
	 * @throws UpdateLexemeException
	 */
	public function updateLexeme( Lexeme $lexeme, /* string */ $editSummary ) {
		throw new UpdateLexemeException();
	}

}

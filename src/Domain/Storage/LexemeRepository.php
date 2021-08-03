<?php

namespace Wikibase\Lexeme\Domain\Storage;

use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeRepository {

	// TODO: createLexeme

	/**
	 * @throws UpdateLexemeException
	 */
	public function updateLexeme( Lexeme $lexeme, string $editSummary );

	/**
	 * @param LexemeId $id
	 *
	 * @return Lexeme|null
	 * @throws GetLexemeException
	 */
	public function getLexemeById( LexemeId $id );

}

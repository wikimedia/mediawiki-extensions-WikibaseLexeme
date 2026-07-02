<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Services;

use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeRetriever {

	public function getLexeme( LexemeId $lexemeId ): ?Lexeme;

}

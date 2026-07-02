<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;

/**
 * @license GPL-2.0-or-later
 */
class EntityLookupLexemeRetriever implements LexemeRetriever {

	public function getLexeme( LexemeId $lexemeId ): ?Lexeme {
		return new Lexeme( $lexemeId );
	}

}

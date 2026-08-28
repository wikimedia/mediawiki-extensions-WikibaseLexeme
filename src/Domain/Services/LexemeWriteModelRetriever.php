<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Services;

use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeWriteModelRetriever {

	public function getLexemeWriteModel( LexemeId $lexemeId ): ?LexemeWriteModel;

}

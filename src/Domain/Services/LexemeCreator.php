<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Services;

use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeCreator {

	public function create( LexemeWriteModel $lexeme, EditMetadata $editMetadata ): LexemeRevision;

}

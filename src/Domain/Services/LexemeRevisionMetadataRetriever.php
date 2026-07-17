<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Domain\Services;

use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\LatestLexemeRevisionMetadataResult;

/**
 * @license GPL-2.0-or-later
 */
interface LexemeRevisionMetadataRetriever {

	public function getLatestRevisionMetadata( LexemeId $lexemeId ): LatestLexemeRevisionMetadataResult;

}

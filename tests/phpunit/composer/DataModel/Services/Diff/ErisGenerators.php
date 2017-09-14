<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators\LexemeGenerator;

/**
 * @license GPL-2.0+
 */
class ErisGenerators {

	public static function lexeme( LexemeId $lexemeId ) {
		return new LexemeGenerator( $lexemeId );
	}

}

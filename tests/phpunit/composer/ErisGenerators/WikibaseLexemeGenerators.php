<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0+
 */
class WikibaseLexemeGenerators {

	public static function lexeme( LexemeId $lexemeId ) {
		return new LexemeGenerator( $lexemeId );
	}

}

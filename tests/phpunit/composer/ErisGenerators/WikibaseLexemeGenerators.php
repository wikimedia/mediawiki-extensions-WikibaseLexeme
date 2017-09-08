<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0+
 */
class WikibaseLexemeGenerators {

	public static function lexeme( LexemeId $lexemeId = null ) {
		return new LexemeGenerator( $lexemeId );
	}

	public static function form( FormId $formId ) {
		return new FormGenerator( $formId );
	}

}

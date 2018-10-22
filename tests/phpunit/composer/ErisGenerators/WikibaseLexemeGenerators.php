<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeGenerators {

	public static function lexeme( LexemeId $lexemeId = null ) {
		return new LexemeGenerator( $lexemeId );
	}

	public static function form( FormId $formId ) {
		return new FormGenerator( $formId );
	}

	public static function sense( SenseId $senseId ) {
		return new SenseGenerator( $senseId );
	}

}

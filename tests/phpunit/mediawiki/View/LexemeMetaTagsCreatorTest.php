<?php


namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\View\LexemeMetaTagsCreator;
use Wikibase\View\Tests\EntityMetaTagsCreatorTestCase;

/**
 * @license GPL-2.0-or-later
 * @covers \Wikibase\Lexeme\View\LexemeMetaTagsCreator
 */
class LexemeMetaTagsCreatorTest extends EntityMetaTagsCreatorTestCase {

	public function provideTestGetMetaTags() {
		$mockLocalizer = $this->createMock( 'MessageLocalizer' );
		$mockLocalizer->method( 'msg' )
			->will( $this->returnValue( '/' ) );

		$lexemeMetaTags = new LexemeMetaTagsCreator( $mockLocalizer );

		return [
			[
				$lexemeMetaTags,
				new Lexeme( new LexemeId( 'L84384' ) ),
				[ 'title' => 'L84384' ]
			],
			[
				$lexemeMetaTags,
				new Lexeme(
					new LexemeId( 'L84389' ),
					new TermList( [ new Term( 'en', 'goat' ), new Term( 'fr', 'taog' ) ] ) ),
				[ 'title' => 'goat / taog' ]
			]
		];
	}

}

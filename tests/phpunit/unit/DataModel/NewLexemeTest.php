<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

/**
 * @covers \Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme
 *
 * @license GPL-2.0-or-later
 */
class NewLexemeTest extends \MediaWikiUnitTestCase {

	public function testWithSenseCreatesSenseWithCorrectParentEntityId() {
		$lexeme = NewLexeme::havingId( 'L7' )
			->withSense( NewSense::havingId( 'S9' ) )
			->build();

		$this->assertSame( 'L7-S9', $lexeme->getSenses()->toArray()[0]->getId()->serialize() );
	}

}

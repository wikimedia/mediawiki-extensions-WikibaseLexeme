<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Tests\Unit\DataModel\NewSense
 *
 * @license GPL-2.0-or-later
 */
class NewSenseTest extends \MediaWikiUnitTestCase {

	public function testAndLexemeWithStringLexemeIdCreatesSenseWithCorrectParentEntityId() {
		$sense = NewSense::havingId( 'S9' )->andLexeme( 'L74' )->build();

		$this->assertSame( 'L74-S9', $sense->getId()->serialize() );
	}

	public function testAndLexemeWithLexemeIdCreatesSenseWithCorrectParentEntityId() {
		$sense = NewSense::havingId( 'S9' )->andLexeme( new LexemeId( 'L74' ) )->build();

		$this->assertSame( 'L74-S9', $sense->getId()->serialize() );
	}

	public function testAndLexemeWithLexemeObjectCreatesSenseWithCorrectParentEntityId() {
		$sense = NewSense::havingId( 'S9' )
			->andLexeme(
				NewLexeme::havingId( 'L74' )->build()
			)
			->build();

		$this->assertSame( 'L74-S9', $sense->getId()->serialize() );
	}

}

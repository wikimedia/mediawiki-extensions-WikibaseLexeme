<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\LexemeSubEntityId
 *
 * @license GPL-2.0-or-later
 */
class LexemeSubEntityIdTest extends MediaWikiUnitTestCase {

	public function testFormatSerialization() {
		$this->assertSame(
			'L1-F7',
			LexemeSubEntityId::formatSerialization( new LexemeId( 'L1' ), 'F', 7 )
		);
		$this->assertSame(
			'L1-S1',
			LexemeSubEntityId::formatSerialization( new LexemeId( 'L1' ), 'S', 1 )
		);
	}

}

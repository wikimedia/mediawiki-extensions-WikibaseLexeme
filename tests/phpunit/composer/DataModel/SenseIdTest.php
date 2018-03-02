<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * @covers \Wikibase\Lexeme\DataModel\SenseId
 *
 * @license GPL-2.0-or-later
 */
class SenseIdTest extends TestCase {

	public function testCanBeCreated() {
		$id = new SenseId( 'S1' );

		$this->assertSame( 'S1', $id->getSerialization() );
	}

}

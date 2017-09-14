<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\Lexeme\DataModel\SenseId;

/**
 * @covers \Wikibase\Lexeme\DataModel\SenseId
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class SenseIdTest extends \PHPUnit_Framework_TestCase {

	public function testCanBeCreated() {
		$id = new SenseId( 'S1' );

		$this->assertSame( 'S1', $id->getSerialization() );
	}

}

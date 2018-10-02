<?php

namespace Wikibase\Lexeme\Tests\DummyObjects;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DummyObjects\DummyFormId;
use Wikibase\Lexeme\DummyObjects\NullFormId;

/**
 * @covers \Wikibase\Lexeme\DummyObjects\DummyFormId
 *
 * @license GPL-2.0-or-later
 */
class DummyFormIdTest extends TestCase {

	public function testCompareToNullFormId_yieldsTrue() {
		$dummyFormId = new DummyFormId( 'L1-F1' );
		$nullFormId = new NullFormId();

		$this->assertTrue( $dummyFormId->equals( $nullFormId ) );
	}

	public function testCompareToIdenticalDummyFormId_yieldsTrue() {
		$dummyFormId = new DummyFormId( 'L1-F1' );
		$otherDummyFormId = new DummyFormId( 'L1-F1' );

		$this->assertTrue( $dummyFormId->equals( $otherDummyFormId ) );
	}

	public function testCompareToOtherDummyFormId_yieldsFalse() {
		$dummyFormId = new DummyFormId( 'L1-F1' );
		$otherDummyFormId = new DummyFormId( 'L1-F2' );

		$this->assertFalse( $dummyFormId->equals( $otherDummyFormId ) );
	}

}

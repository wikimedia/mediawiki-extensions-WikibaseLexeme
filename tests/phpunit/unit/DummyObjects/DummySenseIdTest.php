<?php

namespace Wikibase\Lexeme\Tests\Unit\DummyObjects;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\DummyObjects\DummySenseId;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\DummySenseId
 *
 * @license GPL-2.0-or-later
 */
class DummySenseIdTest extends MediaWikiUnitTestCase {

	public function testCompareToNullSenseId_yieldsTrue() {
		$dummySenseId = new DummySenseId( 'L1-S1' );
		$nullSenseId = new NullSenseId();

		$this->assertTrue( $dummySenseId->equals( $nullSenseId ) );
	}

	public function testCompareToIdenticalDummySenseId_yieldsTrue() {
		$dummySenseId = new DummySenseId( 'L1-S1' );
		$otherDummySenseId = new DummySenseId( 'L1-S1' );

		$this->assertTrue( $dummySenseId->equals( $otherDummySenseId ) );
	}

	public function testCompareToOtherDummySenseId_yieldsFalse() {
		$dummySenseId = new DummySenseId( 'L1-S1' );
		$otherDummySenseId = new DummySenseId( 'L1-S2' );

		$this->assertFalse( $dummySenseId->equals( $otherDummySenseId ) );
	}

}

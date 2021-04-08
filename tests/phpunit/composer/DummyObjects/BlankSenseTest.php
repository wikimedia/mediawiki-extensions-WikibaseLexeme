<?php

namespace Wikibase\Lexeme\Tests\DummyObjects;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\DummyObjects\DummySenseId;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\BlankSense
 *
 * @license GPL-2.0-or-later
 */
class BlankSenseTest extends TestCase {

	public function testGetIdWithoutConnectedLexeme_yieldsNullSenseId() {
		$blankSense = new BlankSense();
		$this->assertInstanceOf( NullSenseId::class, $blankSense->getId() );
	}

	public function testSetsDummyIdFromSenseId() {
		$blankSense = new BlankSense();
		$senseId = new SenseId( 'L1-S2' );
		$blankSense->setId( $senseId );

		$this->assertInstanceOf( DummySenseId::class, $blankSense->getId() );
		$this->assertSame( $senseId->getSerialization(), $blankSense->getId()->getSerialization() );
	}

}

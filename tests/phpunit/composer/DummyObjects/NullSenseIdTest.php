<?php

namespace Wikibase\Lexeme\Tests\DummyObjects;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\DummyObjects\DummySenseId;
use Wikibase\Lexeme\DummyObjects\NullSenseId;

/**
 * @covers \Wikibase\Lexeme\DummyObjects\NullSenseId
 *
 * @license GPL-2.0-or-later
 */
class NullSenseIdTest extends TestCase {

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testGetLexemeId_throwsException() {
		$nullSenseId = new NullSenseId();
		$nullSenseId->getLexemeId();
	}

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testSerialize_throwsException() {
		$nullSenseId = new NullSenseId();
		$nullSenseId->serialize();
	}

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testUnserialize_throwsException() {
		$nullSenseId = new NullSenseId();
		$nullSenseId->unserialize( 'ff' );
	}

	public function testEquals_alwaysReturnsTrue() {
		$nullSenseId = new NullSenseId();

		$this->assertTrue( $nullSenseId->equals( new NullSenseId() ) );
		$this->assertTrue( $nullSenseId->equals( new SenseId( 'L1-S7' ) ) );
		$this->assertTrue( $nullSenseId->equals( new DummySenseId( new LexemeId( 'L9' ) ) ) );
		$this->assertTrue( $nullSenseId->equals( 'gg' ) );
	}

}

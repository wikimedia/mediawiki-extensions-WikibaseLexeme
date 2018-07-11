<?php

namespace Wikibase\Lexeme\Tests\DataTransfer;

use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataTransfer\DummySenseId;
use Wikibase\Lexeme\DataTransfer\NullSenseId;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Wikibase\Lexeme\DataTransfer\NullSenseId
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

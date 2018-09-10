<?php

namespace Wikibase\Lexeme\Tests\DataTransfer;

use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataTransfer\DummySenseId;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataTransfer\NullSenseId;

/**
 * @covers \Wikibase\Lexeme\DataTransfer\DummySenseId
 *
 * @license GPL-2.0-or-later
 */
class DummySenseIdTest extends TestCase {

	public function testConstruction_setsLexemeId() {
		$lexemeId = new LexemeId( 'L1' );
		$dummySenseId = new DummySenseId( $lexemeId );
		$this->assertSame( $lexemeId, $dummySenseId->getLexemeId() );
	}

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testSerialize_throwsException() {
		$dummySenseId = new DummySenseId( new LexemeId( 'L1' ) );
		$dummySenseId->serialize();
	}

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testUnserialize_throwsException() {
		$dummySenseId = new DummySenseId( new LexemeId( 'L1' ) );
		$dummySenseId->unserialize( 'ff' );
	}

	public function testCompareToNullSenseId_yieldsTrue() {
		$lexemeId = new LexemeId( 'L1' );
		$dummySenseId = new DummySenseId( $lexemeId );
		$nullSenseId = new NullSenseId();

		$this->assertTrue( $dummySenseId->equals( $nullSenseId ) );
	}

	public function testCompareToSimilarDummySenseId_yieldsTrue() {
		$lexemeId = new LexemeId( 'L1' );
		$dummySenseId = new DummySenseId( $lexemeId );
		$otherDummySenseId = new DummySenseId( $lexemeId );

		$this->assertTrue( $dummySenseId->equals( $otherDummySenseId ) );
	}

	public function testCompareToOtherDummySenseId_yieldsFalse() {
		$dummySenseId = new DummySenseId( new LexemeId( 'L1' ) );
		$otherDummySenseId = new DummySenseId( new LexemeId( 'L2' ) );

		$this->assertFalse( $dummySenseId->equals( $otherDummySenseId ) );
	}

	public function testGetIdSuffixReturnsEmptyString() {
		$this->assertSame(
			( new DummySenseId( new LexemeId( 'L123' ) ) )->getIdSuffix(),
			''
		);
	}

}

<?php

namespace Wikibase\Lexeme\Tests\DataTransfer;

use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataTransfer\DummyFormId;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataTransfer\NullFormId;

/**
 * @covers \Wikibase\Lexeme\DataTransfer\DummyFormId
 *
 * @license GPL-2.0-or-later
 */
class DummyFormIdTest extends TestCase {

	public function testConstruction_setsLexemeId() {
		$lexemeId = new LexemeId( 'L1' );
		$dummyFormId = new DummyFormId( $lexemeId );
		$this->assertSame( $lexemeId, $dummyFormId->getLexemeId() );
	}

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testSerialize_throwsException() {
		$dummyFormId = new DummyFormId( new LexemeId( 'L1' ) );
		$dummyFormId->serialize();
	}

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testUnserialize_throwsException() {
		$dummyFormId = new DummyFormId( new LexemeId( 'L1' ) );
		$dummyFormId->unserialize( 'ff' );
	}

	public function testCompareToNullFormId_yieldsTrue() {
		$lexemeId = new LexemeId( 'L1' );
		$dummyFormId = new DummyFormId( $lexemeId );
		$nullFormId = new NullFormId();

		$this->assertTrue( $dummyFormId->equals( $nullFormId ) );
	}

	public function testCompareToSimilarDummyFormId_yieldsTrue() {
		$lexemeId = new LexemeId( 'L1' );
		$dummyFormId = new DummyFormId( $lexemeId );
		$otherDummyFormId = new DummyFormId( $lexemeId );

		$this->assertTrue( $dummyFormId->equals( $otherDummyFormId ) );
	}

	public function testCompareToOtherDummyFormId_yieldsFalse() {
		$dummyFormId = new DummyFormId( new LexemeId( 'L1' ) );
		$otherDummyFormId = new DummyFormId( new LexemeId( 'L2' ) );

		$this->assertFalse( $dummyFormId->equals( $otherDummyFormId ) );
	}

}

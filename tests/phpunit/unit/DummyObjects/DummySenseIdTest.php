<?php

namespace Wikibase\Lexeme\Tests\Unit\DummyObjects;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\DummyObjects\DummySenseId;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\DummySenseId
 *
 * @license GPL-2.0-or-later
 */
class DummySenseIdTest extends MediaWikiUnitTestCase {

	public function testConstruction_setsLexemeId() {
		$lexemeId = new LexemeId( 'L1' );
		$dummySenseId = new DummySenseId( $lexemeId );
		$this->assertSame( $lexemeId, $dummySenseId->getLexemeId() );
	}

	public function testSerialize_throwsException() {
		$dummySenseId = new DummySenseId( new LexemeId( 'L1' ) );
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Shall never be called' );
		$dummySenseId->serialize();
	}

	public function testUnserialize_throwsException() {
		$dummySenseId = new DummySenseId( new LexemeId( 'L1' ) );
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Shall never be called' );
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

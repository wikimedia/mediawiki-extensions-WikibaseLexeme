<?php

namespace Wikibase\Lexeme\Tests\Unit\DummyObjects;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\DummyObjects\DummySenseId;
use Wikibase\Lexeme\Domain\DummyObjects\NullSenseId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\NullSenseId
 *
 * @license GPL-2.0-or-later
 */
class NullSenseIdTest extends MediaWikiUnitTestCase {

	public function testGetLexemeId_throwsException() {
		$nullSenseId = new NullSenseId();
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Shall never be called' );
		$nullSenseId->getLexemeId();
	}

	public function testSerialize_throwsException() {
		$nullSenseId = new NullSenseId();
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Shall never be called' );
		$nullSenseId->serialize();
	}

	public function testUnserialize_throwsException() {
		$nullSenseId = new NullSenseId();
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Shall never be called' );
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

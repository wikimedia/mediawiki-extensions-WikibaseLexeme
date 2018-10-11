<?php

namespace Wikibase\Lexeme\Tests\DummyObjects;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Domain\DummyObjects\NullFormId;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\NullFormId
 *
 * @license GPL-2.0-or-later
 */
class NullFormIdTest extends TestCase {

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testGetLexemeId_throwsException() {
		$nullFormId = new NullFormId();
		$nullFormId->getLexemeId();
	}

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testSerialize_throwsException() {
		$nullFormId = new NullFormId();
		$nullFormId->serialize();
	}

	/**
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Shall never be called
	 */
	public function testUnserialize_throwsException() {
		$nullFormId = new NullFormId();
		$nullFormId->unserialize( 'ff' );
	}

	public function testEquals_alwaysReturnsTrue() {
		$nullFormId = new NullFormId();

		$this->assertTrue( $nullFormId->equals( new NullFormId() ) );
		$this->assertTrue( $nullFormId->equals( new FormId( 'L1-F7' ) ) );
		$this->assertTrue( $nullFormId->equals( new DummyFormId( 'L1-F1' ) ) );
		$this->assertTrue( $nullFormId->equals( 'gg' ) );
	}

}

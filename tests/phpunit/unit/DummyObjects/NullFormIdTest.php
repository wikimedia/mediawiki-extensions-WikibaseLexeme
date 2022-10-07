<?php

namespace Wikibase\Lexeme\Tests\Unit\DummyObjects;

use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Domain\DummyObjects\NullFormId;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @covers \Wikibase\Lexeme\Domain\DummyObjects\NullFormId
 *
 * @license GPL-2.0-or-later
 */
class NullFormIdTest extends MediaWikiUnitTestCase {

	public function testGetLexemeId_throwsException() {
		$nullFormId = new NullFormId();
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Shall never be called' );
		$nullFormId->getLexemeId();
	}

	public function testSerialize_throwsException() {
		$nullFormId = new NullFormId();
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Shall never be called' );
		serialize( $nullFormId );
	}

	/** @dataProvider unserializeMethodProvider */
	public function testUnserialize_throwsException( string $method, ...$args ) {
		$nullFormId = new NullFormId();
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Shall never be called' );
		$nullFormId->$method( ...$args );
	}

	public function unserializeMethodProvider(): iterable {
		yield 'PHP < 7.4' => [ 'unserialize', 'ff' ];
		yield 'PHP >= 7.4' => [ '__unserialize', [ 'serialization' => 'ff' ] ];
	}

	public function testEquals_alwaysReturnsTrue() {
		$nullFormId = new NullFormId();

		$this->assertTrue( $nullFormId->equals( new NullFormId() ) );
		$this->assertTrue( $nullFormId->equals( new FormId( 'L1-F7' ) ) );
		$this->assertTrue( $nullFormId->equals( new DummyFormId( 'L1-F1' ) ) );
		$this->assertTrue( $nullFormId->equals( 'gg' ) );
	}

}

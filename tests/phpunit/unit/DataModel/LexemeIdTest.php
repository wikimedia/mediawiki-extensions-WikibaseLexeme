<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use InvalidArgumentException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\LexemeId
 *
 * @license GPL-2.0-or-later
 */
class LexemeIdTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider idSerializationProvider
	 */
	public function testCanConstructId( string $serialization, string $normalizedSerialization ) {
		$id = new LexemeId( $serialization );
		$this->assertSame( $normalizedSerialization, $id->getSerialization() );
	}

	/**
	 * @dataProvider idSerializationProvider
	 */
	public function testSerializationWorksProperly( string $serialization ) {
		$expected = new LexemeId( $serialization );

		/** @var LexemeId $unserialized */
		$unserialized = unserialize( serialize( $expected ) );

		$this->assertTrue( $expected->equals( $unserialized ), 'equality as defined in EntityId' );
	}

	public static function idSerializationProvider(): iterable {
		return [
			[ 'l1', 'L1' ],
			[ 'l100', 'L100' ],
			[ 'l1337', 'L1337' ],
			[ 'l31337', 'L31337' ],
			[ 'L31337', 'L31337' ],
			[ 'L42', 'L42' ],
			[ 'L2147483647', 'L2147483647' ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotConstructWithInvalidSerialization( string $invalidSerialization ) {
		$this->expectException( InvalidArgumentException::class );
		new LexemeId( $invalidSerialization );
	}

	public static function invalidIdSerializationProvider(): iterable {
		return [
			[ "L1\n" ],
			[ 'l' ],
			[ 'p1' ],
			[ 'll1' ],
			[ '1l' ],
			[ 'l01' ],
			[ 'l 1' ],
			[ ' l1' ],
			[ 'l1 ' ],
			[ '1' ],
			[ ' ' ],
			[ '' ],
			[ '0' ],
			[ 'L2147483648' ],
			[ 'L99999999999' ],
			[ ':L42', 'L42' ],
			[ 'foo:L42' ],
			[ 'foo:bar:l42' ],
		];
	}

	public function testGetEntityType() {
		$this->assertSame( 'lexeme', ( new LexemeId( 'L1' ) )->getEntityType() );
	}

	public function testSerialize() {
		$id = new LexemeId( 'L1' );
		$this->assertSame( [ 'serialization' => 'L1' ], $id->__serialize() );
	}

	public function testUnserialize() {
		$id = new LexemeId( 'L1' );
		$id->__unserialize( [ 'serialization' => 'L2' ] );
		$this->assertSame( 'L2', $id->getSerialization() );
	}

	public function testUnserializeInvalid(): void {
		$id = new LexemeId( 'L1' );
		$this->expectException( InvalidArgumentException::class );
		$id->__unserialize( [ 'serialization' => 'l' ] );
	}

	public function testUnserializeNotNormalized(): void {
		$id = new LexemeId( 'L1' );
		$this->expectException( InvalidArgumentException::class );
		$id->__unserialize( [ 'serialization' => 'l2' ] );
		// 'l2' is allowed in the constructor (silently uppercased) but not in unserialize()
	}

	public function testGetNumericId() {
		$id = new LexemeId( 'L1' );
		$this->assertSame( 1, $id->getNumericId() );
		$id = new LexemeId( 'L42' );
		$this->assertSame( 42, $id->getNumericId() );
	}

}

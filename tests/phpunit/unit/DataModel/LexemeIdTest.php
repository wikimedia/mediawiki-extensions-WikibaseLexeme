<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use InvalidArgumentException;
use MediaWikiUnitTestCase;
use RuntimeException;
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
	public function testCanConstructId( $serialization, $normalizedSerialization ) {
		$id = new LexemeId( $serialization );
		$this->assertSame( $normalizedSerialization, $id->getSerialization() );
	}

	/**
	 * @dataProvider idSerializationProvider
	 */
	public function testSerializationWorksProperly( $serialization ) {
		$expected = new LexemeId( $serialization );

		/** @var LexemeId $unserialized */
		$unserialized = unserialize( serialize( $expected ) );

		$this->assertTrue( $expected->equals( $unserialized ), 'equality as defined in EntityId' );

		$this->assertSame(
			$expected->getRepositoryName(),
			$unserialized->getRepositoryName(),
			'getRepositoryName works as expected after unserialize'
		);
		$this->assertSame(
			$expected->getLocalPart(),
			$unserialized->getLocalPart(),
			'getLocalPart works as expected after unserialize'
		);
	}

	public function idSerializationProvider() {
		return [
			[ 'l1', 'L1' ],
			[ 'l100', 'L100' ],
			[ 'l1337', 'L1337' ],
			[ 'l31337', 'L31337' ],
			[ 'L31337', 'L31337' ],
			[ 'L42', 'L42' ],
			[ ':L42', 'L42' ],
			[ 'foo:L42', 'foo:L42' ],
			[ 'foo:bar:l42', 'foo:bar:L42' ],
			[ 'L2147483647', 'L2147483647' ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotConstructWithInvalidSerialization( $invalidSerialization ) {
		$this->expectException( InvalidArgumentException::class );
		new LexemeId( $invalidSerialization );
	}

	public function invalidIdSerializationProvider() {
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
			[ 0 ],
			[ 1 ],
			[ 'L2147483648' ],
			[ 'L99999999999' ],
		];
	}

	public function testGetEntityType() {
		$this->assertSame( 'lexeme', ( new LexemeId( 'L1' ) )->getEntityType() );
	}

	public function testSerialize() {
		$id = new LexemeId( 'L1' );
		$this->assertSame( 'L1', $id->serialize() );
		$this->assertSame( [ 'serialization' => 'L1' ], $id->__serialize() );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testUnserialize( $json, $expected ) {
		$id = new LexemeId( 'L1' );
		$id->unserialize( $json );
		$this->assertSame( $expected, $id->getSerialization() );
		$id->__unserialize( [ 'serialization' => $json ] );
		$this->assertSame( $expected, $id->getSerialization() );
	}

	public function serializationProvider() {
		return [
			[ 'L2', 'L2' ],

			// All these cases are kind of an injection vector and allow constructing invalid ids.
			[ 'L2', 'L2' ],
			[ 'string', 'string' ],
			[ '', '' ],
			[ 2, 2 ],
			[ null, '' ],
		];
	}

	public function testGetNumericId() {
		$id = new LexemeId( 'L1' );
		$this->assertSame( 1, $id->getNumericId() );
		$id = new LexemeId( 'L42' );
		$this->assertSame( 42, $id->getNumericId() );
	}

	public function testGetNumericIdThrowsExceptionOnForeignIds() {
		$this->expectException( RuntimeException::class );
		( new LexemeId( 'foo:L42' ) )->getNumericId();
	}

}

<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\Lexeme\DataModel\LexemeId;
use InvalidArgumentException;
use RuntimeException;

/**
 * @covers Wikibase\Lexeme\DataModel\LexemeId
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexemeIdTest extends \PHPUnit_Framework_TestCase {

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
		$id = new LexemeId( $serialization );
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
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
		$this->setExpectedException( InvalidArgumentException::class );
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
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testUnserialize( $json, $expected ) {
		$id = new LexemeId( 'L1' );
		$id->unserialize( $json );
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
			[ null, null ],
		];
	}

	public function testGetNumericId() {
		$id = new LexemeId( 'L1' );
		$this->assertSame( 1, $id->getNumericId() );
		$id = new LexemeId( 'L42' );
		$this->assertSame( 42, $id->getNumericId() );
	}

	public function testGetNumericIdThrowsExceptionOnForeignIds() {
		$this->setExpectedException( RuntimeException::class );
		( new LexemeId( 'foo:L42' ) )->getNumericId();
	}

}

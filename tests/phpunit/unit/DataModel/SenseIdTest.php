<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use InvalidArgumentException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\LexemeSubEntityId
 * @covers \Wikibase\Lexeme\Domain\Model\SenseId
 *
 * @license GPL-2.0-or-later
 */
class SenseIdTest extends MediaWikiUnitTestCase {

	public function testGivenValidSerialization_allGettersBehaveConsistent() {
		$id = new SenseId( 'L1-S1' );
		$this->assertSame( 'L1-S1', $id->getSerialization() );
	}

	public static function provideInvalidSerializations(): iterable {
		return [
			[ '' ],
			[ '1' ],
			[ 'L1-S' ],
			[ 'L1-S0' ],
			[ 'L0-S1' ],
			[ '  L1-S1  ' ],
			[ "L1-S1\n" ],
			[ 'P1' ],
			[ 'L1-F1' ],
		];
	}

	/**
	 * @dataProvider provideInvalidSerializations
	 */
	public function testGivenInvalidSerialization_constructorThrowsAnException( string $id ) {
		$this->expectException( InvalidArgumentException::class );
		new SenseId( $id );
	}

	public function testPhpSerializationRoundtrip() {
		$id = new SenseId( 'L1-S1' );
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

	/** @dataProvider provideInvalidSerializations */
	public function testGivenInvalidSerialization_unserializeThrowsAnException( string $id ): void {
		$senseId = new SenseId( 'L1-S1' );
		$this->expectException( InvalidArgumentException::class );
		$senseId->__unserialize( [ 'serialization' => $id ] );
	}

	/**
	 * @dataProvider provideLexemeIdMatchingSenseId
	 */
	public function testGetLexemeId_yieldsIdMatchingLocalPart( string $expectedLexemeId, string $givenSenseId ) {
		$id = new SenseId( $givenSenseId );
		$lexemeId = $id->getLexemeId();

		$this->assertInstanceOf( LexemeId::class, $lexemeId );
		$this->assertSame( $expectedLexemeId, $lexemeId->getSerialization() );
	}

	public static function provideLexemeIdMatchingSenseId(): iterable {
		yield [ 'L1', 'L1-S1' ];
		yield [ 'L777', 'L777-S123' ];
	}

	/**
	 * @dataProvider idSuffixProvider
	 */
	public function testGetIdSuffix( string $expected, string $senseIdSerialization ) {
		$this->assertSame(
			$expected,
			( new SenseId( $senseIdSerialization ) )->getIdSuffix()
		);
	}

	public static function idSuffixProvider(): iterable {
		yield [ 'S1', 'L1-S1' ];
		yield [ 'S123', 'L321-S123' ];
	}

}

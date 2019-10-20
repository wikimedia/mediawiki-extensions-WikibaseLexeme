<?php

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
		$id = new SenseId( ':L1-S1' );
		$this->assertSame( 'L1-S1', $id->getSerialization() );
		$this->assertSame( '', $id->getRepositoryName(), 'getRepositoryName' );
		$this->assertSame( 'L1-S1', $id->getLocalPart(), 'getLocalPart' );
		$this->assertFalse( $id->isForeign(), 'isForeign' );
	}

	public function testGivenNonEmptyPrefix_allGettersBehaveConsistent() {
		$id = new SenseId( 'repo:L1-S1' );
		$this->assertSame( 'repo:L1-S1', $id->getSerialization() );
		$this->assertSame( 'repo', $id->getRepositoryName(), 'getRepositoryName' );
		$this->assertSame( 'L1-S1', $id->getLocalPart(), 'getLocalPart' );
		$this->assertTrue( $id->isForeign(), 'isForeign' );
	}

	public function provideInvalidSerializations() {
		return [
			[ null ],
			[ '' ],
			[ 1 ],
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
	public function testGivenInvalidSerialization_constructorThrowsAnException( $id ) {
		$this->expectException( InvalidArgumentException::class );
		new SenseId( $id );
	}

	public function testPhpSerializationRoundtrip() {
		$id = new SenseId( 'repo:L1-S1' );
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

	/**
	 * @dataProvider provideLexemeIdMatchingSenseId
	 */
	public function testGetLexemeId_yieldsIdMatchingLocalPart( $expectedLexemeId, $givenSenseId ) {
		$id = new SenseId( $givenSenseId );
		$lexemeId = $id->getLexemeId();

		$this->assertInstanceOf( LexemeId::class, $lexemeId );
		$this->assertSame( $expectedLexemeId, $lexemeId->getSerialization() );
	}

	public function provideLexemeIdMatchingSenseId() {
		yield [ 'L1', 'repo:L1-S1' ];
		yield [ 'L777', ':L777-S123' ];
	}

	/**
	 * @dataProvider idSuffixProvider
	 */
	public function testGetIdSuffix( $expected, $senseIdSerialization ) {
		$this->assertSame(
			$expected,
			( new SenseId( $senseIdSerialization ) )->getIdSuffix()
		);
	}

	public function idSuffixProvider() {
		yield [ 'S1', 'L1-S1' ];
		yield [ 'S123', 'foreign:L321-S123' ];
	}

}

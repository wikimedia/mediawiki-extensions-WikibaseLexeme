<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\Domain\DataModel\FormId;
use Wikibase\Lexeme\Domain\DataModel\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Domain\DataModel\FormId
 * @covers \Wikibase\Lexeme\Domain\DataModel\LexemeSubEntityId
 *
 * @license GPL-2.0-or-later
 */
class FormIdTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGivenValidSerialization_allGettersBehaveConsistent() {
		$id = new FormId( ':L1-F1' );
		$this->assertSame( 'L1-F1', $id->getSerialization() );
		$this->assertSame( '', $id->getRepositoryName(), 'getRepositoryName' );
		$this->assertSame( 'L1-F1', $id->getLocalPart(), 'getLocalPart' );
		$this->assertFalse( $id->isForeign(), 'isForeign' );
	}

	public function testGivenNonEmptyPrefix_allGettersBehaveConsistent() {
		$id = new FormId( 'repo:L1-F1' );
		$this->assertSame( 'repo:L1-F1', $id->getSerialization() );
		$this->assertSame( 'repo', $id->getRepositoryName(), 'getRepositoryName' );
		$this->assertSame( 'L1-F1', $id->getLocalPart(), 'getLocalPart' );
		$this->assertTrue( $id->isForeign(), 'isForeign' );
	}

	public function provideInvalidSerializations() {
		return [
			[ null ],
			[ '' ],
			[ 1 ],
			[ '1' ],
			[ 'L1-F' ],
			[ 'L1-F0' ],
			[ 'L0-F1' ],
			[ '  L1-F1  ' ],
			[ "L1-F1\n" ],
			[ 'P1' ],
		];
	}

	/**
	 * @dataProvider provideInvalidSerializations
	 */
	public function testGivenInvalidSerialization_constructorThrowsAnException( $id ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new FormId( $id );
	}

	public function testPhpSerializationRoundtrip() {
		$id = new FormId( 'repo:L1-F1' );
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

	/**
	 * @dataProvider provideLexemeIdMatchingFormId
	 */
	public function testGetLexemeId_yieldsIdMatchingLocalPart( $expectedLexemeId, $givenFormId ) {
		$id = new FormId( $givenFormId );
		$lexemeId = $id->getLexemeId();

		$this->assertInstanceOf( LexemeId::class, $lexemeId );
		$this->assertSame(
			( new LexemeId( $expectedLexemeId ) )->serialize(),
			$lexemeId->serialize()
		);
	}

	public function provideLexemeIdMatchingFormId() {
		yield [ 'L1', 'repo:L1-F1' ];
		yield [ 'L777', ':L777-F123' ];
	}

	/**
	 * @dataProvider idSuffixProvider
	 */
	public function testGetIdSuffix( $expected, $formIdSerialization ) {
		$this->assertSame(
			$expected,
			( new FormId( $formIdSerialization ) )->getIdSuffix()
		);
	}

	public function idSuffixProvider() {
		yield [ 'F1', 'L1-F1' ];
		yield [ 'F123', 'foreign:L321-F123' ];
	}

}

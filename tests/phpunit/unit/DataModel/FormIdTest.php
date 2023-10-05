<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use InvalidArgumentException;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\FormId
 * @covers \Wikibase\Lexeme\Domain\Model\LexemeSubEntityId
 *
 * @license GPL-2.0-or-later
 */
class FormIdTest extends MediaWikiUnitTestCase {

	public function testGivenValidSerialization_allGettersBehaveConsistent() {
		$id = new FormId( 'L1-F1' );
		$this->assertSame( 'L1-F1', $id->getSerialization() );
	}

	public static function provideInvalidSerializations() {
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
		$this->expectException( InvalidArgumentException::class );
		new FormId( $id );
	}

	public function testPhpSerializationRoundtrip() {
		$id = new FormId( 'L1-F1' );
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

	/** @dataProvider provideInvalidSerializations */
	public function testGivenInvalidSerialization_unserializeThrowsAnException( $id ): void {
		$id = new FormId( 'L1-F1' );
		$this->expectException( InvalidArgumentException::class );
		$id->__unserialize( [ 'serialization' => $id ] );
	}

	/**
	 * @dataProvider provideLexemeIdMatchingFormId
	 */
	public function testGetLexemeId_yieldsIdMatchingLocalPart( $expectedLexemeId, $givenFormId ) {
		$id = new FormId( $givenFormId );
		$lexemeId = $id->getLexemeId();

		$this->assertInstanceOf( LexemeId::class, $lexemeId );
		$this->assertSame(
			( new LexemeId( $expectedLexemeId ) )->getSerialization(),
			$lexemeId->getSerialization()
		);
	}

	public static function provideLexemeIdMatchingFormId() {
		yield [ 'L1', 'L1-F1' ];
		yield [ 'L777', 'L777-F123' ];
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

	public static function idSuffixProvider() {
		yield [ 'F1', 'L1-F1' ];
		yield [ 'F123', 'L321-F123' ];
	}

}

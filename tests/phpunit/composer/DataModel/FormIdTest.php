<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @covers \Wikibase\Lexeme\DataModel\FormId
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class FormIdTest extends PHPUnit_Framework_TestCase {

	public function testGivenValidSerialization_allGettersBehaveConsistent() {
		$id = new FormId( 'L1-F1' );
		$this->assertSame( 'L1-F1', $id->getSerialization() );
		$this->assertSame( '', $id->getRepositoryName(), 'getRepositoryName' );
		$this->assertSame( 'L1-F1', $id->getLocalPart(), 'getLocalPart' );
		$this->assertFalse( $id->isForeign(), 'isForeign' );
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
			[ ':L1-F1' ],
			[ 'repo:L1-F1' ],
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
		$id = new FormId( 'L1-F1' );
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

}

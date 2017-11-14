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

	public function testGivenValidSerialization_getSerializationReturnsIt() {
		$id = new FormId( 'L1-F1' );
		$this->assertSame( 'L1-F1', $id->getSerialization() );
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

}

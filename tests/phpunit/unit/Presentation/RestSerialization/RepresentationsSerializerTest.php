<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representation;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representations;
use Wikibase\Lexeme\Presentation\RestSerialization\RepresentationsSerializer;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\RepresentationsSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RepresentationsSerializerTest extends TestCase {
	/** @dataProvider representationsProvider */
	public function testSerialize( Representations $representations, ArrayObject $serialization ): void {
		$this->assertEquals( $serialization, ( new RepresentationsSerializer() )->serialize( $representations ) );
	}

	public static function representationsProvider(): Generator {
		yield 'empty' => [ new Representations(), new ArrayObject() ];
		yield 'multiple' => [
			new Representations(
				new Representation( 'en-gb', 'colourized' ),
				new Representation( 'en-us', 'colorized' ),
			),
			new ArrayObject( [ 'en-gb' => 'colourized', 'en-us' => 'colorized' ] ),
		];
	}
}

<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\ReadModel\Gloss;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;
use Wikibase\Lexeme\Presentation\RestSerialization\GlossesSerializer;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\GlossesSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GlossesSerializerTest extends TestCase {

	/**
	 * @dataProvider glossesProvider
	 */
	public function testSerialize( Glosses $glosses, ArrayObject $serialization ): void {
		$this->assertEquals(
			$serialization,
			( new GlossesSerializer() )->serialize( $glosses )
		);
	}

	public static function glossesProvider(): Generator {
		yield 'empty' => [
			new Glosses(),
			new ArrayObject( [] ),
		];

		yield 'multiple glosses' => [
			new Glosses(
				new Gloss( 'en', 'a domesticated animal' ),
				new Gloss( 'de', 'ein Haustier' ),
			),
			new ArrayObject( [
				'en' => 'a domesticated animal',
				'de' => 'ein Haustier',
			] ),
		];
	}
}

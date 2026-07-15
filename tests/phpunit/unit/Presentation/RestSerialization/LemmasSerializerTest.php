<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemma;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Presentation\RestSerialization\LemmasSerializer;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\LemmasSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LemmasSerializerTest extends TestCase {

	/**
	 * @dataProvider lemmasProvider
	 */
	public function testSerialize( Lemmas $lemmas, ArrayObject $serialization ): void {
		$this->assertEquals(
			$serialization,
			( new LemmasSerializer() )->serialize( $lemmas )
		);
	}

	public static function lemmasProvider(): Generator {
		yield 'empty' => [
			new Lemmas(),
			new ArrayObject( [] ),
		];

		yield 'multiple lemmas' => [
			new Lemmas(
				new Lemma( 'en-us', 'color' ),
				new Lemma( 'en-ca', 'colour' ),
			),
			new ArrayObject( [
				'en-us' => 'color',
				'en-ca' => 'colour',
			] ),
		];
	}
}

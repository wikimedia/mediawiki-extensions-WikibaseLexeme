<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Presentation\RestSerialization\GrammaticalFeaturesSerializer;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\GrammaticalFeaturesSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GrammaticalFeaturesSerializerTest extends TestCase {

	/** @dataProvider grammaticalFeaturesProvider */
	public function testSerialize( GrammaticalFeatures $grammaticalFeatures, array $serialization ): void {
		$this->assertSame(
			$serialization,
			( new GrammaticalFeaturesSerializer() )->serialize( $grammaticalFeatures )
		);
	}

	public static function grammaticalFeaturesProvider(): Generator {
		yield 'empty' => [ new GrammaticalFeatures(), [] ];
		yield 'multiple' => [
			new GrammaticalFeatures( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
			[ 'Q1', 'Q2' ],
		];
	}
}

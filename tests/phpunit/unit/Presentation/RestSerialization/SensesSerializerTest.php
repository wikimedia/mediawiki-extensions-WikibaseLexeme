<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\ReadModel\Gloss;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;
use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\RestSerialization\SenseSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\SensesSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\SensesSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SensesSerializerTest extends TestCase {

	/**
	 * @dataProvider sensesProvider
	 */
	public function testSerialize( Senses $senses, array $serialization ): void {
		$senseSerializer = $this->createStub( SenseSerializer::class );
		$senseSerializer->method( 'serialize' )->willReturnCallback(
			static fn ( Sense $sense ) => [ 'id' => $sense->id->getSerialization() ]
		);

		$this->assertEquals( $serialization, ( new SensesSerializer( $senseSerializer ) )->serialize( $senses ) );
	}

	public static function sensesProvider(): Generator {
		yield 'empty' => [ new Senses(), [] ];

		yield 'single sense' => [
			new Senses( self::newSense( 'L1-S1' ) ),
			[ [ 'id' => 'L1-S1' ] ],
		];

		yield 'multiple senses' => [
			new Senses( self::newSense( 'L1-S1' ), self::newSense( 'L1-S2' ) ),
			[ [ 'id' => 'L1-S1' ], [ 'id' => 'L1-S2' ] ],
		];
	}

	private static function newSense( string $senseId ): Sense {
		return new Sense(
			new SenseId( $senseId ),
			new Glosses( new Gloss( 'en', 'a domesticated animal' ) ),
			new StatementList()
		);
	}
}

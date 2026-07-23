<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\ReadModel\Gloss;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;
use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\RestSerialization\GlossesSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\SensesSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\SensesSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SensesSerializerTest extends TestCase {

	private const SERIALIZED_STATEMENTS = [ 'P1' => [ 'a serialized statement' ] ];

	/**
	 * @dataProvider sensesProvider
	 */
	public function testSerialize( Senses $senses, array $serialization ): void {
		$statementListSerializer = $this->createStub( StatementListSerializer::class );
		$statementListSerializer->method( 'serialize' )
			->willReturn( new ArrayObject( self::SERIALIZED_STATEMENTS ) );

		$this->assertEquals(
			$serialization,
			( new SensesSerializer( new GlossesSerializer(), $statementListSerializer ) )
				->serialize( $senses )
		);
	}

	public static function sensesProvider(): Generator {
		$statements = new ArrayObject( self::SERIALIZED_STATEMENTS );

		yield 'empty' => [
			new Senses(),
			[],
		];

		yield 'single sense' => [
			new Senses(
				new Sense(
					new SenseId( 'L1-S1' ),
					new Glosses( new Gloss( 'en', 'a domesticated animal' ) ),
					new StatementList()
				)
			),
			[
				[
					'id' => 'L1-S1',
					'glosses' => new ArrayObject( [ 'en' => 'a domesticated animal' ] ),
					'statements' => $statements,
				],
			],
		];

		yield 'multiple senses' => [
			new Senses(
				new Sense(
					new SenseId( 'L1-S1' ),
					new Glosses( new Gloss( 'en', 'a domesticated animal' ) ),
					new StatementList()
				),
				new Sense(
					new SenseId( 'L1-S2' ),
					new Glosses(
						new Gloss( 'en', 'a wild animal' ),
						new Gloss( 'de', 'ein wildes Tier' )
					),
					new StatementList()
				)
			),
			[
				[
					'id' => 'L1-S1',
					'glosses' => new ArrayObject( [ 'en' => 'a domesticated animal' ] ),
					'statements' => $statements,
				],
				[
					'id' => 'L1-S2',
					'glosses' => new ArrayObject( [
						'en' => 'a wild animal',
						'de' => 'ein wildes Tier',
					] ),
					'statements' => $statements,
				],
			],
		];
	}
}

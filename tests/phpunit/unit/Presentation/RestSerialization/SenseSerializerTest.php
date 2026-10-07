<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\ReadModel\Gloss;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;
use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\RestSerialization\GlossesSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\SenseSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\SenseSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SenseSerializerTest extends TestCase {

	public function testSerialize(): void {
		$glosses = [ 'en' => 'a wild animal', 'de' => 'ein wildes Tier' ];
		$glossesSerializer = $this->createStub( GlossesSerializer::class );
		$glossesSerializer->method( 'serialize' )->willReturn( new ArrayObject( $glosses ) );

		$statements = [ 'P1' => [ 'a serialized statement' ] ];
		$statementListSerializer = $this->createStub( StatementListSerializer::class );
		$statementListSerializer->method( 'serialize' )->willReturn(
			new ArrayObject( $statements )
		);

		$sense = new Sense(
			new SenseId( 'L1-S1' ),
			new Glosses(
				new Gloss( 'en', 'a wild animal' ),
				new Gloss( 'de', 'ein wildes Tier' )
			),
			new StatementList()
		);

		$this->assertEquals(
			[
				'id' => 'L1-S1',
				'glosses' => new ArrayObject( $glosses ),
				'statements' => new ArrayObject( $statements ),
			],
			( new SenseSerializer( $glossesSerializer, $statementListSerializer ) )->serialize( $sense )
		);
	}
}

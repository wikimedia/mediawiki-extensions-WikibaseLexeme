<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Presentation\RestSerialization\LemmasSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\SensesSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LexemeSerializerTest extends TestCase {

	public function testSerialize(): void {
		$id = new LexemeId( 'L1' );
		$lemmas = $this->createStub( Lemmas::class );
		$lexicalCategory = new ItemId( 'Q1' );
		$language = new ItemId( 'Q2' );
		$statements = $this->createStub( StatementList::class );
		$senses = $this->createStub( Senses::class );
		$lexeme = new Lexeme( $id, $lemmas, $lexicalCategory, $language, $statements, $senses );

		$serializedLemmas = new ArrayObject( [ 'en' => 'colour' ] );
		$serializedStatements = new ArrayObject( [ 'P1' => [ 'a serialized statement' ] ] );
		$serializedSenses = [ [ 'id' => 'L1-S1' ] ];

		$lemmasSerializer = $this->createMock( LemmasSerializer::class );
		$lemmasSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $lemmas )
			->willReturn( $serializedLemmas );

		$statementListSerializer = $this->createMock( StatementListSerializer::class );
		$statementListSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $statements )
			->willReturn( $serializedStatements );

		$sensesSerializer = $this->createMock( SensesSerializer::class );
		$sensesSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $senses )
			->willReturn( $serializedSenses );

		$this->assertEquals(
			[
				'id' => "$id",
				'lemmas' => $serializedLemmas,
				'lexical_category' => "$lexicalCategory",
				'language' => "$language",
				'statements' => $serializedStatements,
				'senses' => $serializedSenses,
			],
			( new LexemeSerializer(
				$lemmasSerializer,
				$statementListSerializer,
				$sensesSerializer,
			) )->serialize( $lexeme )
		);
	}
}

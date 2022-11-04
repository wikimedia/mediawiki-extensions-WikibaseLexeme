<?php

namespace Wikibase\Lexeme\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;

/**
 * @covers \Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor
 *
 * @license GPL-2.0-or-later
 */
class SensesStatementEntityReferenceExtractorTest extends TestCase {

	/**
	 * @dataProvider nonLexemeProvider
	 */
	public function testGivenNotALexeme_throwsException( EntityDocument $entity ) {
		$extractor = new SensesStatementEntityReferenceExtractor(
			$this->createMock( StatementEntityReferenceExtractor::class )
		);
		$this->expectException( InvalidArgumentException::class );
		$extractor->extractEntityIds( $entity );
	}

	public function nonLexemeProvider() {
		return [
			[ new Item() ],
			[ new Property( null, null, 'string' ) ],
		];
	}

	public function testGivenLexemeWithoutSenses_returnsEmptyArray() {
		$extractor = new SensesStatementEntityReferenceExtractor(
			$this->createMock( StatementEntityReferenceExtractor::class )
		);
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->assertEquals( [], $extractor->extractEntityIds( $lexeme ) );
	}

	public function testGivenLexemeWithOneSense_returnsEntityIdsOfReferencedEntitiesInStatements() {
		$sense = NewSense::havingId( 'S1' )->build();
		$lexeme = NewLexeme::havingId( 'L3' )
			->withSense( $sense )
			->build();
		$expected = [ new NumericPropertyId( 'P123' ), new ItemId( 'Q42' ) ];

		$statementEntityReferenceExtractor = $this->createMock( StatementEntityReferenceExtractor::class );
		$statementEntityReferenceExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $sense )
			->willReturn( $expected );
		$extractor = new SensesStatementEntityReferenceExtractor( $statementEntityReferenceExtractor );

		$this->assertEquals(
			$expected,
			$extractor->extractEntityIds( $lexeme )
		);
	}

	public function testGivenLexemeWithMultipleSenses_returnsEntityIdsMergedAndUnique() {
		$lexeme = NewLexeme::havingId( 'L171' )
			->withSense( NewSense::havingId( 'S1' ) )
			->withSense( NewSense::havingId( 'S2' ) )
			->withSense( NewSense::havingId( 'S3' ) )
			->build();

		$statementEntityReferenceExtractor = $this->createMock( StatementEntityReferenceExtractor::class );
		$statementEntityReferenceExtractor->expects( $this->exactly( 3 ) )
			->method( 'extractEntityIds' )
			->willReturnOnConsecutiveCalls(
				[],
				[ new NumericPropertyId( 'P123' ), new ItemId( 'Q42' ), new ItemId( 'Q64' ) ],
				[ new NumericPropertyId( 'P321' ), new ItemId( 'Q42' ) ]
			);

		$extractor = new SensesStatementEntityReferenceExtractor( $statementEntityReferenceExtractor );

		$this->assertEquals(
			[
				new NumericPropertyId( 'P123' ),
				new ItemId( 'Q42' ),
				new ItemId( 'Q64' ),
				new NumericPropertyId( 'P321' )
			],
			$extractor->extractEntityIds( $lexeme )
		);
	}

}

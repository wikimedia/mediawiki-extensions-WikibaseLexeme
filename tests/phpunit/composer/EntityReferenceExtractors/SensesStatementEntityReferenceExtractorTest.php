<?php

namespace Wikibase\Lexeme\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lexeme\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;

/**
 * @covers \Wikibase\Lexeme\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor
 *
 * @license GPL-2.0-or-later
 */
class SensesStatementEntityReferenceExtractorTest extends TestCase {

	/**
	 * @dataProvider nonLexemeProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNotALexeme_throwsException( EntityDocument $entity ) {
		$extractor = new SensesStatementEntityReferenceExtractor(
			$this->getMockStatementEntityReferenceExtractor()
		);
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
			$this->getMockStatementEntityReferenceExtractor()
		);
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->assertEquals( [], $extractor->extractEntityIds( $lexeme ) );
	}

	public function testGivenLexemeWithOneSense_returnsEntityIdsOfReferencedEntitiesInStatements() {
		$sense = NewSense::havingId( 'S1' )->build();
		$lexeme = NewLexeme::havingId( 'L3' )
			->withSense( $sense )
			->build();
		$expected = [ new PropertyId( 'P123' ), new ItemId( 'Q42' ) ];

		$statementEntityReferenceExtractor = $this->getMockStatementEntityReferenceExtractor();
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

		$statementEntityReferenceExtractor = $this->getMockStatementEntityReferenceExtractor();
		$statementEntityReferenceExtractor->expects( $this->exactly( 3 ) )
			->method( 'extractEntityIds' )
			->willReturnOnConsecutiveCalls(
				[],
				[ new PropertyId( 'P123' ), new ItemId( 'Q42' ), new ItemId( 'Q64' ) ],
				[ new PropertyId( 'P321' ), new ItemId( 'Q42' ) ]
			);

		$extractor = new SensesStatementEntityReferenceExtractor( $statementEntityReferenceExtractor );

		$this->assertEquals(
			[ new PropertyId( 'P123' ), new ItemId( 'Q42' ), new ItemId( 'Q64' ), new PropertyId( 'P321' ) ],
			$extractor->extractEntityIds( $lexeme )
		);
	}

	/**
	 * @return StatementEntityReferenceExtractor|MockObject
	 */
	private function getMockStatementEntityReferenceExtractor() {
		return $this->getMockBuilder( StatementEntityReferenceExtractor::class )
			->disableOriginalConstructor()
			->getMock();
	}

}

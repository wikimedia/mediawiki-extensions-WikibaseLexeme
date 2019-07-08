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
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;

/**
 * @covers \Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor
 *
 * @license GPL-2.0-or-later
 */
class FormsStatementEntityReferenceExtractorTest extends TestCase {

	/**
	 * @dataProvider nonLexemeProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNotALexeme_throwsException( EntityDocument $entity ) {
		$extractor = new FormsStatementEntityReferenceExtractor(
			$this->getMockStatementEntityReferenceExtractor()
		);
		$extractor->extractEntityIds( $entity );
	}

	public function testGivenLexemeWithoutForms_returnsEmptyArray() {
		$extractor = new FormsStatementEntityReferenceExtractor(
			$this->getMockStatementEntityReferenceExtractor()
		);
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->assertEquals( [], $extractor->extractEntityIds( $lexeme ) );
	}

	public function nonLexemeProvider() {
		return [
			[ new Item() ],
			[ new Property( null, null, 'string' ) ],
		];
	}

	public function testGivenLexemeWithOneForm_returnsEntityIdsOfReferencedEntitiesInStatements() {
		$form = NewForm::havingId( 'F1' )->build();
		$lexeme = NewLexeme::havingId( 'L3' )
			->withForm( $form )
			->build();
		$expected = [ new PropertyId( 'P123' ), new ItemId( 'Q42' ) ];

		$statementEntityReferenceExtractor = $this->getMockStatementEntityReferenceExtractor();
		$statementEntityReferenceExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $form )
			->willReturn( $expected );
		$extractor = new FormsStatementEntityReferenceExtractor( $statementEntityReferenceExtractor );

		$this->assertEquals(
			$expected,
			$extractor->extractEntityIds( $lexeme )
		);
	}

	public function testGivenLexemeWithMultipleForms_returnsEntityIdsMergedAndUnique() {
		$lexeme = NewLexeme::havingId( 'L171' )
			->withForm( NewForm::any() )
			->withForm( NewForm::any() )
			->withForm( NewForm::any() )
			->build();

		$statementEntityReferenceExtractor = $this->getMockStatementEntityReferenceExtractor();
		$statementEntityReferenceExtractor->expects( $this->exactly( 3 ) )
			->method( 'extractEntityIds' )
			->willReturnOnConsecutiveCalls(
				[],
				[ new PropertyId( 'P123' ), new ItemId( 'Q42' ), new ItemId( 'Q64' ) ],
				[ new PropertyId( 'P321' ), new ItemId( 'Q42' ) ]
			);

		$extractor = new FormsStatementEntityReferenceExtractor( $statementEntityReferenceExtractor );

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

<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLexicalCategory;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Validators\CompositeValidator;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLexicalCategory
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpLexicalCategoryTest extends TestCase {

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALexicalCategoryProvider_validateThrowsException(
		EntityDocument $entity
	) {
		$changeOp = $this->newChangeOpLexicalCategory( new ItemId( 'Q2' ) );

		$this->expectException( InvalidArgumentException::class );
		$changeOp->validate( $entity );
	}

	public function invalidEntityProvider() {
		return [
			[ $this->createMock( EntityDocument::class ) ],
			[ new Item( new ItemId( 'Q234' ) ) ],
		];
	}

	private function newChangeOpLexicalCategory( ItemId $id ) {
		return new ChangeOpLexicalCategory(
			$id,
			new CompositeValidator( [] )
		);
	}

	public function testGivenValidLexicalCategory_validateReturnsValidResult() {
		$changeOp = $this->newChangeOpLexicalCategory( new ItemId( 'Q234' ) );

		$this->assertTrue( $changeOp->validate( new Lexeme() )->isValid() );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALexicalCategoryProvider_applyThrowsException(
		EntityDocument $entity
	) {
		$changeOp = $this->newChangeOpLexicalCategory( new ItemId( 'Q234' ) );

		$this->expectException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

	public function testGivenLexicalCategoryExists_applySetsLexicalCategoryAndSetsTheSummary() {
		$lexicalCategory = new ItemId( 'Q234' );
		$lexeme = new Lexeme( null, null, $lexicalCategory );
		$summary = new Summary();

		$changeOp = $this->newChangeOpLexicalCategory( new ItemId( 'Q432' ) );

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'Q432', $lexeme->getLexicalCategory()->getSerialization() );

		$this->assertSame( 'set', $summary->getMessageKey() );
		$this->assertSame( [ 'Q432' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLexicalCategoryIsNull_applySetsLexicalCategoryAndSetsTheSummary() {
		$lexeme = new Lexeme();
		$summary = new Summary();

		$changeOp = $this->newChangeOpLexicalCategory( new ItemId( 'Q234' ) );

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'Q234', $lexeme->getLexicalCategory()->getSerialization() );

		$this->assertSame( 'set', $summary->getMessageKey() );
		$this->assertSame( [ 'Q234' ], $summary->getAutoSummaryArgs() );
	}

}

<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\ChangeOpLexicalCategory;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\MediaWiki\Validators\LexemeValidatorFactoryTestMockProvider;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Summary;

/**
 * @covers Wikibase\Lexeme\ChangeOp\ChangeOpLexicalCategory
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class ChangeOpLexicalCategoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALexicalCategoryProvider_validateThrowsException(
		EntityDocument $entity
	) {
		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp = new ChangeOpLexicalCategory( new ItemId( 'Q2' ), $this->getLexemeValidatorFactory() );
		$changeOp->validate( $entity );
	}

	public function invalidEntityProvider() {
		return [
			[ $this->getMock( EntityDocument::class ) ],
			[ new Item( new ItemId( 'Q234' ) ) ],
		];
	}

	public function testGivenValidLexicalCategory_validateReturnsValidResult() {
		$lexeme = new Lexeme();

		$changeOp = new ChangeOpLexicalCategory(
			new ItemId( 'Q234' ),
			$this->getLexemeValidatorFactory()
		);

		$this->assertTrue( $changeOp->validate( $lexeme )->isValid() );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALexicalCategoryProvider_applyThrowsException(
		EntityDocument $entity
	) {
		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp = new ChangeOpLexicalCategory(
			new ItemId( 'Q234' ),
			$this->getLexemeValidatorFactory()
		);
		$changeOp->apply( $entity );
	}

	public function testGivenLexicalCategoryExists_applySetsLexicalCategoryAndSetsTheSummary() {
		$lexicalCategory = new ItemId( 'Q234' );
		$lexeme = new Lexeme( null, null, $lexicalCategory );
		$summary = new Summary();

		$changeOp = new ChangeOpLexicalCategory(
			new ItemId( 'Q432' ),
			$this->getLexemeValidatorFactory()
		);

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'Q432', $lexeme->getLexicalCategory()->getSerialization() );

		$this->assertSame( 'set', $summary->getActionName() );
		$this->assertSame( [ 'Q432' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLexicalCategoryIsNull_applySetsLexicalCategoryAndSetsTheSummary() {
		$lexeme = new Lexeme();
		$summary = new Summary();

		$changeOp = new ChangeOpLexicalCategory(
			new ItemId( 'Q234' ),
			$this->getLexemeValidatorFactory()
		);

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'Q234', $lexeme->getLexicalCategory()->getSerialization() );

		$this->assertSame( 'set', $summary->getActionName() );
		$this->assertSame( [ 'Q234' ], $summary->getAutoSummaryArgs() );
	}

	private function getLexemeValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$validatorFactoryMockProvider = new LexemeValidatorFactoryTestMockProvider();
		return $validatorFactoryMockProvider->getLexemeValidatorFactory(
			$this,
			10,
			$mockProvider->getMockTermValidatorFactory(),
			[ 'Q234', 'Q432' ]
		);
	}

}

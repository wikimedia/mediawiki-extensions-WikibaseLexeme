<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLexicalCategory;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Tests\MediaWiki\Validators\LexemeValidatorFactoryTestMockProvider;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLexicalCategory
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpLexicalCategoryTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALexicalCategoryProvider_validateThrowsException(
		EntityDocument $entity
	) {
		$changeOp = $this->newChangeOpLexicalCategory( new ItemId( 'Q2' ) );

		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp->validate( $entity );
	}

	public function invalidEntityProvider() {
		return [
			[ $this->getMock( EntityDocument::class ) ],
			[ new Item( new ItemId( 'Q234' ) ) ],
		];
	}

	private function newChangeOpLexicalCategory( ItemId $id ) {
		return new ChangeOpLexicalCategory(
			$id,
			$this->getLexemeValidatorFactory()->getLexicalCategoryValidator()
		);
	}

	private function getLexemeValidatorFactory() {
		// TODO: this can be simplified since we only need a lexicalCategoryValidator
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$validatorFactoryMockProvider = new LexemeValidatorFactoryTestMockProvider();
		return $validatorFactoryMockProvider->getLexemeValidatorFactory(
			$this,
			10,
			$mockProvider->getMockTermValidatorFactory(),
			[ 'Q234', 'Q432' ]
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

		$this->setExpectedException( InvalidArgumentException::class );
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

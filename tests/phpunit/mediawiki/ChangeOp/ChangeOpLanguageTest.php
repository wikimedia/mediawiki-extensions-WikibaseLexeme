<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLanguage;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLanguage
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpLanguageTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALanguageProvider_validateThrowsException(
		EntityDocument $entity
	) {
		$changeOp = $this->newChangeOpLanguage( new ItemId( 'Q2' ) );
		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp->validate( $entity );
	}

	public function invalidEntityProvider() {
		yield [ $this->getMock( EntityDocument::class ) ];
		yield [ new Item( new ItemId( 'Q123' ) ) ];
	}

	private function newChangeOpLanguage( ItemId $id ) {
		return new ChangeOpLanguage(
			$id,
			new CompositeValidator( [] )
		);
	}

	public function testGivenValidLanguage_validateReturnsValidResult() {
		$changeOp = $this->newChangeOpLanguage( new ItemId( 'Q123' ) );

		$this->assertTrue( $changeOp->validate( new Lexeme() )->isValid() );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALanguageProvider_applyThrowsException( EntityDocument $entity ) {
		$changeOp = $this->newChangeOpLanguage( new ItemId( 'Q123' ) );

		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

	public function testGivenLanguageExists_applySetsLanguageAndSetsTheSummary() {
		$language = new ItemId( 'Q123' );
		$lexeme = new Lexeme( null, null, null, $language );
		$summary = new Summary();

		$this->newChangeOpLanguage( new ItemId( 'Q321' ) )->apply( $lexeme, $summary );

		$this->assertSame( 'Q321', $lexeme->getLanguage()->getSerialization() );

		$this->assertSame( 'set', $summary->getMessageKey() );
		$this->assertSame( [ 'Q321' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLanguageIsNull_applySetsLanguageAndSetsTheSummary() {
		$lexeme = new Lexeme();
		$summary = new Summary();

		$this->newChangeOpLanguage( new ItemId( 'Q123' ) )->apply( $lexeme, $summary );

		$this->assertSame( 'Q123', $lexeme->getLanguage()->getSerialization() );

		$this->assertSame( 'set', $summary->getMessageKey() );
		$this->assertSame( [ 'Q123' ], $summary->getAutoSummaryArgs() );
	}

}

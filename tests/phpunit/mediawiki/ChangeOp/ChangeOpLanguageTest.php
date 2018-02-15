<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\ChangeOpLanguage;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Tests\MediaWiki\Validators\LexemeValidatorFactoryTestMockProvider;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpLanguage
 *
 * @license GPL-2.0+
 */
class ChangeOpLanguageTest extends TestCase {

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALanguageProvider_validateThrowsException(
		EntityDocument $entity
	) {
		$changeOp = new ChangeOpLanguage( new ItemId( 'Q2' ), $this->getLexemeValidatorFactory() );
		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp->validate( $entity );
	}

	public function invalidEntityProvider() {
		return [
			[ $this->getMock( EntityDocument::class ) ],
			[ new Item( new ItemId( 'Q123' ) ) ],
		];
	}

	public function testGivenValidLanguage_validateReturnsValidResult() {
		$lexeme = new Lexeme();

		$changeOp = new ChangeOpLanguage( new ItemId( 'Q123' ), $this->getLexemeValidatorFactory() );

		$this->assertTrue( $changeOp->validate( $lexeme )->isValid() );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALanguageProvider_applyThrowsException( EntityDocument $entity ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp = new ChangeOpLanguage(
			new ItemId( 'Q123' ),
			$this->getLexemeValidatorFactory()
		);
		$changeOp->apply( $entity );
	}

	public function testGivenLanguageExists_applySetsLanguageAndSetsTheSummary() {
		$language = new ItemId( 'Q123' );
		$lexeme = new Lexeme( null, null, null, $language );
		$summary = new Summary();

		$changeOp = new ChangeOpLanguage( new ItemId( 'Q321' ), $this->getLexemeValidatorFactory() );

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'Q321', $lexeme->getLanguage()->getSerialization() );

		$this->assertSame( 'set', $summary->getMessageKey() );
		$this->assertSame( [ 'Q321' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLanguageIsNull_applySetsLanguageAndSetsTheSummary() {
		$lexeme = new Lexeme();
		$summary = new Summary();

		$changeOp = new ChangeOpLanguage(
			new ItemId( 'Q123' ),
			$this->getLexemeValidatorFactory()
		);

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'Q123', $lexeme->getLanguage()->getSerialization() );

		$this->assertSame( 'set', $summary->getMessageKey() );
		$this->assertSame( [ 'Q123' ], $summary->getAutoSummaryArgs() );
	}

	private function getLexemeValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$validatorFactoryMockProvider = new LexemeValidatorFactoryTestMockProvider();
		return $validatorFactoryMockProvider->getLexemeValidatorFactory(
			$this,
			10,
			$mockProvider->getMockTermValidatorFactory(),
			[ 'Q123', 'Q321' ]
		);
	}

}

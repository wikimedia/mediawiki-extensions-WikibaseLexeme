<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\ChangeOp\ChangeOpLanguage;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Summary;

/**
 * @covers Wikibase\Lexeme\ChangeOp\ChangeOpLanguage
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class ChangeOpLanguageTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testGivenInvalidArguments_constructorThrowsException( $language ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new ChangeOpLanguage( $language, $this->getLexemeValidatorFactory() );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			'not a ItemId as a language code (int)' => [ 123 ],
			'not a ItemId as a language code (string)' => [ 'duck' ],
		];
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALanguageProvider_validateThrowsException(
		EntityDocument $entity
	) {
		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp = new ChangeOpLanguage( new ItemId( 'Q2' ), $this->getLexemeValidatorFactory() );
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

	public function testGivenLanguageIsNull_validateReturnsValidResult() {
		$lexeme = new Lexeme();

		$changeOp = new ChangeOpLanguage( null, $this->getLexemeValidatorFactory() );

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

	public function testGivenLanguageIsNull_applyRemovesLanguageForGivenLanguageAndSetsTheSummary() {
		$language = new ItemId( 'Q123' );
		$lexeme = new Lexeme( null, null, null, $language );
		$summary = new Summary();

		$changeOp = new ChangeOpLanguage( null, $this->getLexemeValidatorFactory() );
		$changeOp->apply( $lexeme, $summary );

		$this->assertNull( $lexeme->getLanguage() );

		$this->assertSame( 'remove', $summary->getActionName() );
		$this->assertSame( [ 'Q123' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLanguageExists_applySetsLanguageAndSetsTheSummary() {
		$language = new ItemId( 'Q123' );
		$lexeme = new Lexeme( null, null, null, $language );
		$summary = new Summary();

		$changeOp = new ChangeOpLanguage( new ItemId( 'Q321' ), $this->getLexemeValidatorFactory() );

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'Q321', $lexeme->getLanguage()->getSerialization() );

		$this->assertSame( 'set', $summary->getActionName() );
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

		$this->assertSame( 'set', $summary->getActionName() );
		$this->assertSame( [ 'Q123' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLanguageIsNullAndNoLanguageExists_applyMakesNoChange() {
		$lexeme = new Lexeme();
		$summary = new Summary();

		$changeOp = new ChangeOpLanguage( null, $this->getLexemeValidatorFactory() );
		$changeOp->apply( $lexeme, $summary );

		$this->assertNull( $lexeme->getLanguage() );
		$this->assertNull( $summary->getActionName() );
	}

	private function getLexemeValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return new LexemeValidatorFactory( 10, $mockProvider->getMockTermValidatorFactory() );
	}

}

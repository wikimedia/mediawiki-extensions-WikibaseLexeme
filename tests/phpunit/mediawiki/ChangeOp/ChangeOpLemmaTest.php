<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpLemma;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Summary;

/**
 * @covers Wikibase\Lexeme\ChangeOp\ChangeOpLemma
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class ChangeOpLemmaTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testGivenInvalidArguments_constructorThrowsException( $language, $lemma ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new ChangeOpLemma( $language, $lemma, $this->getLexemeValidatorFactory() );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			'not a string as a language code (null)' => [ null, 'duck' ],
			'not a string as a language code (int)' => [ 123, 'duck' ],
			'not a string as a lemma term (bool)' => [ 'en', true ],
			'not a string as a lemma term (int)' => [ 'en', 123 ],
		];
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALemmasProvider_validateThrowsException( EntityDocument $entity ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp = new ChangeOpLemma( 'en', 'duck', $this->getLexemeValidatorFactory() );
		$changeOp->validate( $entity );
	}

	public function invalidEntityProvider() {
		return [
			[ $this->getMock( EntityDocument::class ) ],
			[ new Item( new ItemId( 'Q123' ) ) ],
		];
	}

	public function testGivenInvalidLanguageCode_validateReturnsInvalidResult() {
		$lexeme = new Lexeme();

		$changeOp = new ChangeOpLemma( 'INVALID', 'duck', $this->getLexemeValidatorFactory() );

		$this->assertFalse( $changeOp->validate( $lexeme )->isValid() );
	}

	/**
	 * @dataProvider invalidLemmaTermProvider
	 */
	public function testGivenInvalidLemmaTerm_validateReturnsInvalidResult( $lemmaTerm ) {
		$lexeme = new Lexeme();

		$changeOp = new ChangeOpLemma( 'en', $lemmaTerm, $this->getLexemeValidatorFactory() );

		$this->assertFalse( $changeOp->validate( $lexeme )->isValid() );
	}

	public function invalidLemmaTermProvider() {
		return [
			'empty string' => [ '' ],
			'too long text' => [ 'Lorem ipsum dolor sit amet' ],
		];
	}

	public function testGivenValidLanguageCodeAndLemmaTerm_validateReturnsValidResult() {
		$lexeme = new Lexeme();

		$changeOp = new ChangeOpLemma( 'en', 'duck', $this->getLexemeValidatorFactory() );

		$this->assertTrue( $changeOp->validate( $lexeme )->isValid() );
	}

	public function testGivenLemmaTermIsNull_validateReturnsValidResult() {
		$lexeme = new Lexeme();

		$changeOp = new ChangeOpLemma( 'en', null, $this->getLexemeValidatorFactory() );

		$this->assertTrue( $changeOp->validate( $lexeme )->isValid() );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALemmasProvider_applyThrowsException( EntityDocument $entity ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp = new ChangeOpLemma( 'en', 'duck', $this->getLexemeValidatorFactory() );
		$changeOp->apply( $entity );
	}

	public function testGivenLemmaTermIsNull_applyRemovesLemmaForGivenLanguageAndSetsTheSummary() {
		$lemmas = new TermList( [
			new Term( 'de', 'Ente' ),
			new Term( 'en', 'duck' ),
		] );
		$lexeme = new Lexeme( null, $lemmas );
		$summary = new Summary();

		$changeOp = new ChangeOpLemma( 'de', null, $this->getLexemeValidatorFactory() );
		$changeOp->apply( $lexeme, $summary );

		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'de' ) );
		$this->assertTrue( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
		$this->assertSame( 'duck', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );

		$this->assertSame( 'remove', $summary->getActionName() );
		$this->assertSame( 'de', $summary->getLanguageCode() );
		$this->assertSame( [ 'Ente' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenNoLemmaInGivenLanguage_applyAddsLemmaAndSetsTheSummary() {
		$lemmas = new TermList( [
			new Term( 'en', 'duck' ),
		] );
		$lexeme = new Lexeme( null, $lemmas );
		$summary = new Summary();

		$changeOp = new ChangeOpLemma( 'de', 'Ente', $this->getLexemeValidatorFactory() );

		$changeOp->apply( $lexeme, $summary );

		$this->assertTrue( $lexeme->getLemmas()->hasTermForLanguage( 'de' ) );
		$this->assertSame( 'Ente', $lexeme->getLemmas()->getByLanguage( 'de' )->getText() );
		$this->assertTrue( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
		$this->assertSame( 'duck', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );

		$this->assertSame( 'add', $summary->getActionName() );
		$this->assertSame( 'de', $summary->getLanguageCode() );
		$this->assertSame( [ 'Ente' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLemmaInGivenLanguageExists_applySetsLemmaToNewValueAndSetsTheSummary() {
		$lemmas = new TermList( [
			new Term( 'en', 'foo' ),
		] );
		$lexeme = new Lexeme( null, $lemmas );
		$summary = new Summary();

		$changeOp = new ChangeOpLemma( 'en', 'bar', $this->getLexemeValidatorFactory() );

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'bar', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );

		$this->assertSame( 'set', $summary->getActionName() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertSame( [ 'bar' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLemmaTermIsNullAndNoLemmaInGivenLanguage_applyMakesNoChange() {
		$lexeme = new Lexeme();
		$summary = new Summary();

		$changeOp = new ChangeOpLemma( 'de', null, $this->getLexemeValidatorFactory() );
		$changeOp->apply( $lexeme, $summary );

		$this->assertFalse( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
		$this->assertNull( $summary->getActionName() );
	}

	private function getLexemeValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return new LexemeValidatorFactory( 10, $mockProvider->getMockTermValidatorFactory() );
	}

}

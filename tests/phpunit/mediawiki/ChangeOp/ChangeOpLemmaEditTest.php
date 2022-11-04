<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaEdit
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpLemmaEditTest extends TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testGivenInvalidArguments_constructorThrowsException( $language, $lemma ) {
		$this->expectException( InvalidArgumentException::class );
		new ChangeOpLemmaEdit( $language, $lemma,
			$this->createMock( LemmaTermValidator::class ) );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			'not a string as a language code (null)' => [ null, 'duck' ],
			'not a string as a language code (int)' => [ 123, 'duck' ],
			'not a string as a lemma term (bool)' => [ 'en', true ],
			'not a string as a lemma term (int)' => [ 'en', 123 ],
			'not a string as a lemma term (null)' => [ 'en', null ],
		];
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALemmasProvider_validateThrowsException( EntityDocument $entity ) {
		$this->expectException( InvalidArgumentException::class );
		$changeOp = new ChangeOpLemmaEdit( 'en', 'duck',
			$this->createMock( LemmaTermValidator::class ) );
		$changeOp->validate( $entity );
	}

	public function invalidEntityProvider() {
		return [
			[ $this->createMock( EntityDocument::class ) ],
			[ new Item( new ItemId( 'Q123' ) ) ],
		];
	}

	/**
	 * @dataProvider invalidLemmaTermProvider
	 */
	public function testGivenInvalidLemmaTerm_validateReturnsInvalidResult( $lemmaTerm ) {
		$lexeme = new Lexeme();

		$lemmasTermValidator = $this->createMock( LemmaTermValidator::class );
		$lemmasTermValidator
			->expects( $this->once() )
			->method( 'validate' )
			->willReturn( Result::newError( [] ) );
		$changeOp = new ChangeOpLemmaEdit( 'en', $lemmaTerm, $lemmasTermValidator );

		$this->assertFalse( $changeOp->validate( $lexeme )->isValid() );
	}

	public function invalidLemmaTermProvider() {
		return [
			'empty string' => [ '' ],
			'too long text' => [ 'Lorem ipsum dolor sit amet' ],
		];
	}

	public function testGivenValidLemmaTerm_validateReturnsValidResult() {
		$lexeme = new Lexeme();

		$lemmasTermValidator = $this->createMock( LemmaTermValidator::class );
		$lemmasTermValidator
			->expects( $this->once() )
			->method( 'validate' )
			->willReturn( Result::newSuccess() );

		$changeOp = new ChangeOpLemmaEdit( 'en', 'duck', $lemmasTermValidator );

		$this->assertTrue( $changeOp->validate( $lexeme )->isValid() );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 */
	public function testGivenNotALemmasProvider_applyThrowsException( EntityDocument $entity ) {
		$this->expectException( InvalidArgumentException::class );
		$changeOp = new ChangeOpLemmaEdit( 'en', 'duck',
			$this->createMock( LemmaTermValidator::class ) );
		$changeOp->apply( $entity );
	}

	public function testGivenNoLemmaInGivenLanguage_applyAddsLemmaAndSetsTheSummary() {
		$lemmas = new TermList( [
			new Term( 'en', 'duck' ),
		] );
		$lexeme = new Lexeme( null, $lemmas );
		$summary = new Summary();

		$changeOp = new ChangeOpLemmaEdit( 'de', 'Ente',
			$this->createMock( LemmaTermValidator::class ) );

		$changeOp->apply( $lexeme, $summary );

		$this->assertTrue( $lexeme->getLemmas()->hasTermForLanguage( 'de' ) );
		$this->assertSame( 'Ente', $lexeme->getLemmas()->getByLanguage( 'de' )->getText() );
		$this->assertTrue( $lexeme->getLemmas()->hasTermForLanguage( 'en' ) );
		$this->assertSame( 'duck', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );

		$this->assertSame( 'add', $summary->getMessageKey() );
		$this->assertSame( 'de', $summary->getLanguageCode() );
		$this->assertSame( [ 'Ente' ], $summary->getAutoSummaryArgs() );
	}

	public function testGivenLemmaInGivenLanguageExists_applySetsLemmaToNewValueAndSetsTheSummary() {
		$lemmas = new TermList( [
			new Term( 'en', 'foo' ),
		] );
		$lexeme = new Lexeme( null, $lemmas );
		$summary = new Summary();

		$changeOp = new ChangeOpLemmaEdit( 'en', 'bar',
			$this->createMock( LemmaTermValidator::class ) );

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'bar', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );

		$this->assertSame( 'set', $summary->getMessageKey() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertSame( [ 'bar' ], $summary->getAutoSummaryArgs() );
	}

}

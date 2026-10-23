<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpLemmaEdit;
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
		new ChangeOpLemmaEdit( $language, $lemma );
	}

	public static function invalidConstructorArgumentsProvider() {
		return [
			'not a string as a language code (null)' => [ null, 'duck' ],
			'not a string as a language code (int)' => [ 123, 'duck' ],
			'not a string as a lemma term (bool)' => [ 'en', true ],
			'not a string as a lemma term (int)' => [ 'en', 123 ],
			'not a string as a lemma term (null)' => [ 'en', null ],
		];
	}

	public function testGivenNotALemmasProvider_validateThrowsException() {
		$entity = new Item( new ItemId( 'Q123' ) );
		$changeOp = new ChangeOpLemmaEdit( 'en', 'duck' );
		$this->expectException( InvalidArgumentException::class );
		$changeOp->validate( $entity );
	}

	public function testValidateReturnsValidResult() {
		$changeOp = new ChangeOpLemmaEdit( 'en', 'duck' );

		$this->assertTrue( $changeOp->validate( new Lexeme() )->isValid() );
	}

	public function testGivenNotALemmasProvider_applyThrowsException() {
		$entity = new Item( new ItemId( 'Q123' ) );
		$changeOp = new ChangeOpLemmaEdit( 'en', 'duck' );
		$this->expectException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

	public function testGivenNoLemmaInGivenLanguage_applyAddsLemmaAndSetsTheSummary() {
		$lemmas = new TermList( [
			new Term( 'en', 'duck' ),
		] );
		$lexeme = new Lexeme( null, $lemmas );
		$summary = new Summary();

		$changeOp = new ChangeOpLemmaEdit( 'de', 'Ente' );

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

		$changeOp = new ChangeOpLemmaEdit( 'en', 'bar' );

		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 'bar', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );

		$this->assertSame( 'set', $summary->getMessageKey() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertSame( [ 'bar' ], $summary->getAutoSummaryArgs() );
	}

}

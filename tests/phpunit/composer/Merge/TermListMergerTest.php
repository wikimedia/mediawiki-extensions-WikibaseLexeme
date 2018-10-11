<?php

namespace Wikibase\Lexeme\Tests\Merge;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Merge\TermListMerger;

/**
 * @covers \Wikibase\Lexeme\Domain\Merge\TermListMerger
 *
 * @license GPL-2.0-or-later
 */
class TermListMergerTest extends TestCase {

	/**
	 * @dataProvider provideSamples
	 */
	public function testMerge( TermList $expected, TermList $source, TermList $target ) {
		$termListMerger = new TermListMerger();
		$termListMerger->merge( $source, $target );

		$this->assertSame( $expected->toTextArray(), $target->toTextArray() );
	}

	public function provideSamples() {
		yield [
			new TermList(),
			new TermList(),
			new TermList()
		];

		yield [
			new TermList( [ new Term( 'en', 'foo' ) ] ),
			new TermList( [ new Term( 'en', 'foo' ) ] ),
			new TermList()
		];

		yield [
			new TermList( [ new Term( 'de', 'Beispiel' ) ] ),
			new TermList(),
			new TermList( [ new Term( 'de', 'Beispiel' ) ] )
		];

		yield [
			new TermList( [ new Term( 'en-gb', 'bar' ), new Term( 'en', 'foo' ) ] ),
			new TermList( [ new Term( 'en', 'foo' ) ] ),
			new TermList( [ new Term( 'en-gb', 'bar' ) ] ),
		];
	}

	public function testMergeDoesNotChangeReferencesForPreexistingTerms() {
		$sourceTerm = new Term( 'en', 'bar' );
		$source = new TermList( [ $sourceTerm ] );

		$target = $this->getMockBuilder( TermList::class )
			->disableOriginalConstructor()
			->getMock();
		$target->method( 'hasTerm' )
			->willReturn( true );
		$target->expects( $this->never() )
			->method( 'setTerm' );

		$termListMerger = new TermListMerger();
		$termListMerger->merge( $source, $target );
	}

}

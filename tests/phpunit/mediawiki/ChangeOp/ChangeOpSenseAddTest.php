<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpSenseAdd
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseAddTest extends TestCase {

	use PHPUnit4And6Compat;

	public function test_validateFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddSense = new ChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'foo' ) ) ] ),
		] ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpAddSense->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validatePassesIfProvidedEntityIsALexeme() {
		$changeOpAddSense = new ChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'foo' ) ) ] ),
		] ) );

		$result = $changeOpAddSense->validate( NewLexeme::create()->build() );

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddSense = new ChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'foo' ) ) ] ),
		] ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpAddSense->apply( NewItem::withId( 'Q1' )->build() );
	}

	public function test_applyAddsSenseIfGivenALexeme() {
		$glosses = new TermList( [ new Term( 'en', 'furry animal' ) ] );
		$changeOp = new ChangeOpSenseAdd( new ChangeOpSenseEdit( [
				new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
		] ) );
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$changeOp->apply( $lexeme );

		$sense = $lexeme->getSenses()->toArray()[0];
		$this->assertEquals( $glosses, $sense->getGlosses() );
	}

	public function test_applySetsTheSummary() {
		$changeOp = new ChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
		] ) );

		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$summary = new Summary();

		$changeOp->apply( $lexeme, $summary );

		$this->assertEquals( 'add-sense', $summary->getMessageKey() );
		$this->assertEquals( [ 'furry animal' ], $summary->getAutoSummaryArgs() );
	}

}

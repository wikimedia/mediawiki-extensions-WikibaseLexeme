<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseClone;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseAdd
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseAddTest extends TestCase {

	use PHPUnit4And6Compat;

	public function test_validateFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddSense = $this->newChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'foo' ) ) ] ),
		] ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpAddSense->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validatePassesIfProvidedEntityIsALexeme() {
		$changeOpAddSense = $this->newChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'foo' ) ) ] ),
		] ) );

		$result = $changeOpAddSense->validate( NewLexeme::create()->build() );

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddSense = $this->newChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'foo' ) ) ] ),
		] ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpAddSense->apply( NewItem::withId( 'Q1' )->build() );
	}

	public function test_applyAddsSenseIfGivenALexeme() {
		$glosses = new TermList( [ new Term( 'en', 'furry animal' ) ] );
		$changeOp = $this->newChangeOpSenseAdd( new ChangeOpSenseEdit( [
				new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
		] ) );
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$changeOp->apply( $lexeme );

		$sense = $lexeme->getSenses()->toArray()[0];
		$this->assertEquals( $glosses, $sense->getGlosses() );
	}

	public function test_applySetsStatementId() {
		$changeOp = $this->newChangeOpSenseAdd( new ChangeOpSenseClone(
			NewSense::havingId( 'S3' )
				->withGloss( 'en', 'furry animal' )
				->withStatement( NewStatement::noValueFor( 'P5' ) )
				->build()
		) );
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$changeOp->apply( $lexeme );

		$sense = $lexeme->getSenses()->toArray()[0];
		$statement = $sense->getStatements()->toArray()[0];
		$this->assertStringStartsWith( 'L1-S1$', $statement->getGuid() );
	}

	public function test_applySetsTheSummary() {
		$changeOp = $this->newChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [ new ChangeOpGloss( new Term( 'en', 'furry animal' ) ) ] ),
		] ) );

		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$summary = new Summary();

		$changeOp->apply( $lexeme, $summary );

		$this->assertEquals( 'add-sense', $summary->getMessageKey() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertEquals( [ 'furry animal' ], $summary->getAutoSummaryArgs() );
	}

	public function test_applySetsTheSummary_noLanguageIfMultipleGlosses() {
		$changeOp = $this->newChangeOpSenseAdd( new ChangeOpSenseEdit( [
			new ChangeOpGlossList( [
				new ChangeOpGloss( new Term( 'en', 'furry animal' ) ),
				new ChangeOpGloss( new Term( 'de', 'pelziges Tier' ) ),
			] ),
		] ) );
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$summary = new Summary();

		$changeOp->apply( $lexeme, $summary );

		$this->assertEquals( null, $summary->getLanguageCode() );
	}

	private function newChangeOpSenseAdd( ChangeOp $childChangeOp ) {
		return new ChangeOpSenseAdd(
			$childChangeOp,
			new GuidGenerator()
		);
	}

}

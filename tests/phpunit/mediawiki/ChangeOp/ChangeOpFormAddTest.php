<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\Tests\NewItem;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormAdd
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFormAddTest extends TestCase {

	public function test_validateFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddForm = $this->newChangeOpFormAdd( new ChangeOpFormEdit( [
			new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'foo' ) ) ] ),
			new ChangeOpGrammaticalFeatures( [] )
		] ) );

		$this->expectException( \InvalidArgumentException::class );
		$changeOpAddForm->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validatePassesIfProvidedEntityIsALexeme() {
		$changeOpAddForm = $this->newChangeOpFormAdd( new ChangeOpFormEdit( [
			new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'foo' ) ) ] ),
			new ChangeOpGrammaticalFeatures( [] )
		] ) );

		$result = $changeOpAddForm->validate( NewLexeme::create()->build() );

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddForm = $this->newChangeOpFormAdd( new ChangeOpFormEdit( [
			new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'foo' ) ) ] ),
			new ChangeOpGrammaticalFeatures( [] )
		] ) );

		$this->expectException( \InvalidArgumentException::class );
		$changeOpAddForm->apply( NewItem::withId( 'Q1' )->build() );
	}

	public function test_applyAddsFormIfGivenALexeme() {
		$representations = new TermList( [ new Term( 'en', 'goat' ) ] );
		$changeOp = $this->newChangeOpFormAdd( new ChangeOpFormEdit( [
				new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
				new ChangeOpGrammaticalFeatures( [ new ItemId( 'Q1' ) ] )
		] ) );
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$changeOp->apply( $lexeme );

		$form = $lexeme->getForms()->toArray()[0];
		$this->assertEquals( $representations, $form->getRepresentations() );
		$this->assertEquals( [ new ItemId( 'Q1' ) ], $form->getGrammaticalFeatures() );
	}

	public function test_applySetsTheSummary() {
		$changeOp = $this->newChangeOpFormAdd( new ChangeOpFormEdit( [
			new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'goat' ) ) ] ),
			new ChangeOpGrammaticalFeatures( [] )
		] ) );

		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$summary = new Summary();

		$changeOp->apply( $lexeme, $summary );

		$this->assertEquals( 'add-form', $summary->getMessageKey() );
		$this->assertEquals( [ 'goat' ], $summary->getAutoSummaryArgs() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-F1' ], $summary->getCommentArgs() );
	}

	private function newChangeOpFormAdd( ChangeOp $childChangeOp ) {
		return new ChangeOpFormAdd(
			$childChangeOp
		);
	}

}

<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\ChangeOp\ChangeOpGrammaticalFeatures;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentation;
use Wikibase\Lexeme\ChangeOp\ChangeOpRepresentationList;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpFormAdd
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFormAddTest extends TestCase {

	use PHPUnit4And6Compat;

	public function test_validateFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddForm = new ChangeOpFormAdd( new ChangeOpFormEdit( [
			new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'foo' ) ) ] ),
			new ChangeOpGrammaticalFeatures( [] )
		] ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpAddForm->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validatePassesIfProvidedEntityIsALexeme() {
		$changeOpAddForm = new ChangeOpFormAdd( new ChangeOpFormEdit( [
			new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'foo' ) ) ] ),
			new ChangeOpGrammaticalFeatures( [] )
		] ) );

		$result = $changeOpAddForm->validate( NewLexeme::create()->build() );

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddForm = new ChangeOpFormAdd( new ChangeOpFormEdit( [
			new ChangeOpRepresentationList( [ new ChangeOpRepresentation( new Term( 'en', 'foo' ) ) ] ),
			new ChangeOpGrammaticalFeatures( [] )
		] ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpAddForm->apply( NewItem::withId( 'Q1' )->build() );
	}

	public function test_applyAddsFormIfGivenALexeme() {
		$representations = new TermList( [ new Term( 'en', 'goat' ) ] );
		$changeOp = new ChangeOpFormAdd( new ChangeOpFormEdit( [
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
		$changeOp = new ChangeOpFormAdd( new ChangeOpFormEdit( [
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

}

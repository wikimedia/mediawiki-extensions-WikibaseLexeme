<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpRemoveForm
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveFormTest extends TestCase {

	public function test_validateFailsIfProvidedEntityIsNotALexeme() {
		$changeOpRemoveForm = new ChangeOpRemoveForm( new FormId( 'L1-F1' ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpRemoveForm->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validatePassesIfProvidedEntityIsALexeme() {
		$changeOpRemoveForm = new ChangeOpRemoveForm( new FormId( 'L1-F1' ) );

		$result = $changeOpRemoveForm->validate( NewLexeme::create()->build() );

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotALexeme() {
		$changeOpRemoveForm = new ChangeOpRemoveForm( new FormId( 'L1-F1' ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpRemoveForm->apply( NewItem::withId( 'Q1' )->build() );
	}

	public function test_applyRemovesFormIfGivenALexeme() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$form = $lexeme->getForms()->toArray()[0];

		$changeOp = new ChangeOpRemoveForm( $form->getId() );
		$changeOp->apply( $lexeme );

		$this->assertSame( 0, $lexeme->getForms()->count() );
	}

	public function test_applySetsTheSummary() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexeme->addForm( new TermList( [ new Term( 'fr', 'goat' ) ] ), [] );
		$form = $lexeme->getForms()->toArray()[0];

		$changeOp = new ChangeOpRemoveForm( $form->getId() );
		$summary = new Summary();
		$changeOp->apply( $lexeme, $summary );

		$this->assertSame( 0, $lexeme->getForms()->count() );

		$this->assertEquals( 'remove-form', $summary->getMessageKey() );
		$this->assertEquals( [ 'goat' ], $summary->getAutoSummaryArgs() );
		$this->assertEquals( [ $form->getId()->getSerialization() ], $summary->getCommentArgs() );
	}

}

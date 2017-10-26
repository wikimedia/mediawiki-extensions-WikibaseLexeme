<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpAddForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpAddForm
 *
 * @license GPL-2.0+
 */
class ChangeOpAddFormTest extends \PHPUnit_Framework_TestCase {

	public function test_validateFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddForm = new ChangeOpAddForm( new TermList(), [] );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpAddForm->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validatePassesIfProvidedEntityIsALexeme() {
		$changeOpAddForm = new ChangeOpAddForm( new TermList(), [] );

		$result = $changeOpAddForm->validate( NewLexeme::create()->build() );

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotALexeme() {
		$changeOpAddForm = new ChangeOpAddForm( new TermList(), [] );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpAddForm->apply( NewItem::withId( 'Q1' )->build() );
	}

	public function test_applyAddsFormIfGivenALexeme() {
		$representations = new TermList( [ new Term( 'en', 'goat' ) ] );
		$changeOp = new ChangeOpAddForm( $representations, [ new ItemId( 'Q1' ) ] );
		$lexeme = NewLexeme::create()->build();

		$changeOp->apply( $lexeme );

		$form = $lexeme->getForms()->toArray()[0];
		$this->assertEquals( $representations, $form->getRepresentations() );
		$this->assertEquals( [ new ItemId( 'Q1' ) ], $form->getGrammaticalFeatures() );
	}

	public function test_applySetsTheSummary() {
		$representations = new TermList( [ new Term( 'en', 'goat' ) ] );
		$changeOp = new ChangeOpAddForm( $representations, [] );
		$lexeme = NewLexeme::create()->build();

		$summary = new Summary();

		$changeOp->apply( $lexeme, $summary );

		$this->assertEquals( 'add-form', $summary->getMessageKey() );
		$this->assertEquals( [ 'goat' ], $summary->getAutoSummaryArgs() );
	}

}

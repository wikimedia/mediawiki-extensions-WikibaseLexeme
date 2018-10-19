<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveForm;
use Wikibase\Lexeme\Domain\DataModel\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpRemoveForm
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveFormTest extends TestCase {

	use PHPUnit4And6Compat;

	public function test_validateFailsIfProvidedEntityIsNotALexeme() {
		$changeOpRemoveForm = new ChangeOpRemoveForm( new FormId( 'L1-F1' ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpRemoveForm->validate( NewItem::withId( 'Q1' )->build() );
	}

	public function test_validateFailsIfProvidedEntityLacksForm() {
		$changeOpRemoveForm = new ChangeOpRemoveForm( new FormId( 'L1-F1' ) );

		$result = $changeOpRemoveForm->validate( NewLexeme::create()->build() );

		$this->assertFalse( $result->isValid() );
	}

	public function test_validatePassesIfProvidedEntityIsLexemeAndHasForm() {
		$changeOpRemoveForm = new ChangeOpRemoveForm( new FormId( 'L1-F1' ) );

		$result = $changeOpRemoveForm->validate(
			NewLexeme::create()
				->withForm( NewForm::havingId( new FormId( 'L1-F1' ) )->build() )
				->build()
		);

		$this->assertTrue( $result->isValid() );
	}

	public function test_applyFailsIfProvidedEntityIsNotALexeme() {
		$changeOpRemoveForm = new ChangeOpRemoveForm( new FormId( 'L1-F1' ) );

		$this->setExpectedException( \InvalidArgumentException::class );
		$changeOpRemoveForm->apply( NewItem::withId( 'Q1' )->build() );
	}

	public function test_applyRemovesFormIfGivenALexeme() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$form = $lexeme->getForms()->toArray()[0];

		$changeOp = new ChangeOpRemoveForm( $form->getId() );
		$changeOp->apply( $lexeme );

		$this->assertCount( 0, $lexeme->getForms() );
	}

	public function test_applySetsTheSummary() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'fr', 'goat' )
			)
			->build();
		$form = $lexeme->getForms()->toArray()[0];

		$changeOp = new ChangeOpRemoveForm( $form->getId() );
		$summary = new Summary();
		$changeOp->apply( $lexeme, $summary );

		$this->assertCount( 0, $lexeme->getForms() );

		$this->assertEquals( 'remove-form', $summary->getMessageKey() );
		$this->assertEquals( [ 'goat' ], $summary->getAutoSummaryArgs() );
		$this->assertEquals( [ $form->getId()->getSerialization() ], $summary->getCommentArgs() );
	}

}

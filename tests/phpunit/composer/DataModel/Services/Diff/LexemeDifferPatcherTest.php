<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Diff\LexemeDiffer;
use Wikibase\Lexeme\Domain\Diff\LexemePatcher;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\ErisGenerators\ErisTest;
use Wikibase\Lexeme\Tests\ErisGenerators\WikibaseLexemeGenerators;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\Domain\Diff\LexemeDiffer
 * @covers \Wikibase\Lexeme\Domain\Diff\LexemePatcher
 *
 * @license GPL-2.0-or-later
 */
class LexemeDifferPatcherTest extends TestCase {

	use ErisTest;

	public function testProperty_PatchingLexemeWithGeneratedDiffAlwaysRestoresItToTheTargetState() {
		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();

		// Line below is needed to reproduce failures. In case of failure seed will be in the output
		//$this->eris()->seed(1504876177284329)->forAll( ...

		$this->eris()
			->forAll(
				WikibaseLexemeGenerators::lexeme( new LexemeId( 'L1' ) ),
				WikibaseLexemeGenerators::lexeme( new LexemeId( 'L1' ) )
			)
			->then( function ( Lexeme $lexeme1, Lexeme $lexeme2 ) use ( $differ, $patcher ) {
				// Deep cloning is needed because $lexeme1 gets mutated in this test.
				// Because of mutation shrinking will work incorrectly
				$lexeme1 = unserialize( serialize( $lexeme1 ) );

				$patch = $differ->diffEntities( $lexeme1, $lexeme2 );
				$patcher->patchEntity( $lexeme1, $patch );

				$this->assertTrue(
					$lexeme1->getLanguage()->equals( $lexeme2->getLanguage() ),
					'Lexemes have different languages'
				);
				$this->assertTrue(
					$lexeme1->getLexicalCategory()->equals( $lexeme2->getLexicalCategory() ),
					'Lexemes have different lexical categories'
				);
				$this->assertTrue(
					$lexeme1->getStatements()->equals( $lexeme2->getStatements() ),
					'Lexemes have different statements'
				);
				$this->assertTrue(
					$lexeme1->getLemmas()->equals( $lexeme2->getLemmas() ),
					'Lexemes have different lemmas'
				);

				$this->assertEquals(
					$lexeme2->getForms(),
					$lexeme1->getForms(),
					'Lexemes have different forms'
				);
				$this->assertGreaterThanOrEqual(
					$lexeme2->getNextFormId(),
					$lexeme1->getNextFormId()
				);
			} );
	}

	public function testAddedFormIsDiffedAndPatched() {
		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();
		$initialLexeme = NewLexeme::create();
		$lexemeWithoutForm = $initialLexeme->build();
		$lexemeWithForm = $initialLexeme->withForm( NewForm::havingId( 'F1' ) )->build();

		$diff = $differ->diffLexemes( $lexemeWithoutForm, $lexemeWithForm );
		$patcher->patchEntity( $lexemeWithoutForm, $diff );

		$this->assertTrue(
			$lexemeWithoutForm->equals( $lexemeWithForm ),
			"Lexemes are not equal"
		);
	}

	public function testRemovedFormIsDiffedAndPatched() {
		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();
		$initialLexeme = NewLexeme::create();
		$lexemeWithForm = $initialLexeme->withForm( NewForm::havingId( 'F1' ) )->build();
		$lexemeWithoutForm = $initialLexeme->build();

		$diff = $differ->diffLexemes( $lexemeWithForm, $lexemeWithoutForm );
		$patcher->patchEntity( $lexemeWithForm, $diff );

		$this->assertEquals( [], $lexemeWithForm->getForms()->toArray() );
	}

	public function testDiffAndPatchCanIncreaseNextFormIdCounter() {
		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();
		$initialLexeme = NewLexeme::create();
		$lexemeWithoutForm = $initialLexeme->build();
		$lexemeThatHadForm = $initialLexeme->withForm( NewForm::havingId( 'F1' ) )->build();
		$lexemeThatHadForm->removeForm( new FormId( 'L1-F1' ) );

		$diff = $differ->diffLexemes( $lexemeWithoutForm, $lexemeThatHadForm );
		$patcher->patchEntity( $lexemeWithoutForm, $diff );

		$this->assertEquals(
			$lexemeThatHadForm->getNextFormId(),
			$lexemeWithoutForm->getNextFormId()
		);
		$this->assertTrue(
			$lexemeWithoutForm->equals( $lexemeThatHadForm ),
			"Lexemes are not equal"
		);
	}

	public function testDiffAndPatchCanChangeExistingFormRepresentations() {
		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();
		$initialLexeme = NewLexeme::create();
		$lexeme1 = $initialLexeme
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'cat' )
			)->build();
		$lexeme2 = $initialLexeme
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'goat' )
			)->build();

		$diff = $differ->diffLexemes( $lexeme1, $lexeme2 );
		$patcher->patchEntity( $lexeme1, $diff );

		$this->assertTrue(
			$lexeme1->equals( $lexeme2 ),
			"Lexemes are not equal"
		);
	}

	public function testDiffAndPatchCanAtomicallyChangeExistingFormRepresentations() {
		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();
		$initialLexeme = NewLexeme::create();
		$lexeme1 = $initialLexeme
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'en-value' )
			)->build();
		$lexeme2 = $initialLexeme
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'en-value' )
					->andRepresentation( 'fr', 'fr-value' )
			)->build();
		$latestLexeme = $initialLexeme
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'de', 'de-value' )
			)->build();

		$diff = $differ->diffLexemes( $lexeme1, $lexeme2 );
		$patcher->patchEntity( $latestLexeme, $diff );

		$form = $latestLexeme->getForms()->toArray()[0];
		$this->assertEquals(
			'fr-value',
			$form->getRepresentations()->getByLanguage( 'fr' )->getText()
		);
		$this->assertEquals(
			'de-value',
			$form->getRepresentations()->getByLanguage( 'de' )->getText()
		);
	}

	public function testDiffAndPatchCanPatchAllForms() {
		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();
		$initialLexeme = NewLexeme::create();
		$lexeme1 = $initialLexeme
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'value-1-1' )
			)->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'value-1-2' )
			)->build();
		$lexeme2 = $initialLexeme
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'value-2-1' )
			)->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'value-2-2' )
			)->build();

		$diff = $differ->diffLexemes( $lexeme1, $lexeme2 );
		$patcher->patchEntity( $lexeme1, $diff );

		$form1 = $lexeme1->getForm( new FormId( 'L1-F1' ) );
		$form2 = $lexeme1->getForm( new FormId( 'L1-F2' ) );
		$this->assertEquals(
			'value-2-1',
			$form1->getRepresentations()->getByLanguage( 'en' )->getText()
		);
		$this->assertEquals(
			'value-2-2',
			$form2->getRepresentations()->getByLanguage( 'en' )->getText()
		);
	}

}

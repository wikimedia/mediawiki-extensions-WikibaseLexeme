<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Eris\Facade;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher;
use Wikibase\Lexeme\Tests\ErisGenerators\WikibaseLexemeGenerators;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexemeDifferPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testProperty_PatchingLexemeWithGeneratedDiffAlwaysRestoresItToTheTargetState() {
		if ( !class_exists( Facade::class ) ) {
			$this->markTestSkipped( 'Package `giorgiosironi/eris` is not installed. Skipping' );
		}

		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();

		//Lines below is needed to reproduce failures. In case of failure seed will be in the output
		//$seed = 1504876177284329;
		//putenv("ERIS_SEED=$seed");

		$eris = new Facade();

		$eris->forAll(
				WikibaseLexemeGenerators::lexeme( new LexemeId( 'L1' ) ),
				WikibaseLexemeGenerators::lexeme( new LexemeId( 'L1' ) )
			)
			->then( function ( Lexeme $lexeme1, Lexeme $lexeme2 ) use ( $differ, $patcher ) {
				$patch = $differ->diffEntities( $lexeme1, $lexeme2 );
				$patcher->patchEntity( $lexeme1, $patch );

				$this->assertTrue( $lexeme1->equals( $lexeme2 ), 'Lexemes are not equal' );
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

		$this->assertEquals( [], $lexemeWithForm->getForms() );
	}

	public function testDiffAndPatchCanIncreaseNextFormIdCounter() {
		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();
		$initialLexeme = NewLexeme::create();
		$lexemeWithoutForm = $initialLexeme->build();
		$lexemeThatHadForm = $initialLexeme->withForm( NewForm::havingId( 'F1' ) )->build();
		$lexemeThatHadForm->removeForm( new FormId( 'F1' ) );

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

		$form = $latestLexeme->getForms()[0];
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

		$form1 = $lexeme1->getForm( new FormId( 'F1' ) );
		$form2 = $lexeme1->getForm( new FormId( 'F2' ) );
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

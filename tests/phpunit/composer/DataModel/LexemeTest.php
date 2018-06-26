<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use InvalidArgumentException;
use Wikibase\Lexeme\DataModel\LexemePatchAccess;

/**
 * @covers \Wikibase\Lexeme\DataModel\Lexeme
 *
 * @license GPL-2.0-or-later
 */
class LexemeTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testConstructor() {
		$id = new LexemeId( 'L1' );
		$statements = new StatementList();
		$lemma = new Term( 'fa', 'Karaj' );
		$lemmas = new TermList( [ $lemma ] );
		$lexicalCategory = new ItemId( 'Q1' );
		$language = new ItemId( 'Q2' );
		$lexeme = new Lexeme( $id, $lemmas, $lexicalCategory, $language, $statements );

		$this->assertSame( $id, $lexeme->getId() );
		$this->assertSame( $statements, $lexeme->getStatements() );
		$this->assertSame( $lemmas, $lexeme->getLemmas() );
		$this->assertSame( $lexicalCategory, $lexeme->getLexicalCategory() );
		$this->assertSame( $language, $lexeme->getLanguage() );
	}

	public function testEmptyConstructor() {
		$lexeme = new Lexeme();

		$this->assertNull( $lexeme->getId() );
		$this->assertEquals( new StatementList(), $lexeme->getStatements() );
		$this->assertEquals( new TermList(), $lexeme->getLemmas() );
	}

	public function testUninitializedLexicalCategory() {
		$lexeme = new Lexeme();

		$this->setExpectedException( UnexpectedValueException::class );
		$lexeme->getLexicalCategory();
	}

	public function testUninitializedLanguage() {
		$lexeme = new Lexeme();

		$this->setExpectedException( UnexpectedValueException::class );
		$lexeme->getLanguage();
	}

	public function testGetEntityType() {
		$this->assertSame( 'lexeme', ( new Lexeme() )->getType() );
	}

	public function testSetNewId() {
		$lexeme = new Lexeme();
		$id = new LexemeId( 'L1' );
		$lexeme->setId( $id );

		$this->assertSame( $id, $lexeme->getId() );
	}

	public function testOverrideId() {
		//FIXME: This behaviour seems wrong. Should probably be changed
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$id = new LexemeId( 'L2' );
		$lexeme->setId( $id );

		$this->assertSame( $id, $lexeme->getId() );
	}

	public function testCanNotCreateWithNonIntNextFormId() {
		$this->setExpectedException( \Exception::class );
		new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			1.0
		);
	}

	public function testCanNotCreateWithNonPositiveNextFormId() {
		$this->setExpectedException( \Exception::class );
		new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			0
		);
	}

	public function testCanNotCreateWithNextFormIdSmallerOrEqualThanNumberOfProvidedForms() {
		$this->setExpectedException( \Exception::class );
		new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			1,
			new FormSet( [ NewForm::any()->build() ] )
		);
	}

	public function testCanNotCreateWithNextFormIdSmallerOrEqualThanMaxExistingFormId() {
		$this->setExpectedException( \Exception::class );
		new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			1,
			new FormSet( [ NewForm::havingId( 'F1' )->build() ] )
		);
	}

	public function testCanNotCreateWithNonIntNextSenseId() {
		$this->setExpectedException( \Exception::class );
		new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			1,
			null,
			1.0
		);
	}

	public function testCanNotCreateWithNonPositiveNextSenseId() {
		$this->setExpectedException( \Exception::class );
		new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			1,
			null,
			0
		);
	}

	public function testCanNotCreateWithNextSenseIdSmallerOrEqualThanNumberOfProvidedSenses() {
		$this->setExpectedException( \Exception::class );
		new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			1,
			null,
			1,
			[ NewSense::havingId( 'L1-S1' )->build() ]
		);
	}

	public function testCanNotCreateWithNextSenseIdSmallerOrEqualThanMaxExistingSenseId() {
		$this->markTestSkipped( 'max sense ID number not yet checked' );
		$this->setExpectedException( \Exception::class );
		new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			1,
			null,
			2,
			[ NewSense::havingId( 'L1-S2' )->build() ]
		);
	}

	public function provideInvalidIds() {
		return [
			[ null ],
			[ false ],
			[ 1.0 ],
			[ 1 ],
			[ 'L1' ],
			[ new ItemId( 'Q1' ) ],
		];
	}

	/**
	 * @dataProvider provideInvalidIds
	 */
	public function testSetInvalidId( $id ) {
		$lexeme = new Lexeme();

		$this->setExpectedException( InvalidArgumentException::class );
		$lexeme->setId( $id );
	}

	public function testIsEmpty() {
		$this->assertTrue( ( new Lexeme() )->isEmpty() );
		$this->assertTrue( ( new Lexeme( new LexemeId( 'L1' ) ) )->isEmpty() );
	}

	public function testIsNotEmptyWithStatement() {
		$lexeme = new Lexeme();
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertFalse( $lexeme->isEmpty() );
	}

	public function testIsNotEmptyWithLemmas() {
		$lemmas = new TermList( [ new Term( 'zh', 'Beijing' ) ] );
		$lexeme = new Lexeme( new LexemeId( 'l1' ), $lemmas );

		$this->assertFalse( $lexeme->isEmpty() );
	}

	public function testIsNotEmptyWithForms() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			2,
			new FormSet( [ NewForm::any()->andId( new FormId( 'L1-F1' ) )->build() ] )
		);

		$this->assertFalse( $lexeme->isEmpty() );
	}

	public function testIsNotEmptyWithSenses() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			1,
			null,
			2,
			[ NewSense::havingId( 'S1' )->build() ]
		);

		$this->assertFalse( $lexeme->isEmpty() );
	}

	public function testIsNotEmptyWithLemmasAndStatement() {
		$lemmas = new TermList( [ new Term( 'zh', 'Beijing' ) ] );
		$lexeme = new Lexeme( new LexemeId( 'l1' ), $lemmas );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertFalse( $lexeme->isEmpty() );
	}

	public function testIsEmptyWithLexicalCategory() {
		$lexicalCategory = new ItemId( 'Q1' );
		$lexeme = new Lexeme( new LexemeId( 'l1' ), null, $lexicalCategory );

		$this->assertTrue( $lexeme->isEmpty() );
	}

	public function testIsEmptyWithLanguage() {
		$language = new ItemId( 'Q11' );
		$lexeme = new Lexeme( new LexemeId( 'l2' ), null, null, $language );

		$this->assertTrue( $lexeme->isEmpty() );
	}

	private function newLexemeWithForm( Form $form ) {
		return new Lexeme(
			new LexemeId( 'L1' ),
			null,
			null,
			null,
			null,
			2,
			new FormSet( [ $form ] )
		);
	}

	public function provideTestEquals() {
		$empty = new Lexeme();

		$withStatement = new Lexeme();
		$withStatement->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$newForm = NewForm::havingId( 'F1' );
		$newFormGoat = $newForm->andRepresentation( 'en', 'goat' );
		$newFormGoatFeatureQ1 = $newFormGoat->andGrammaticalFeature( 'Q1' );
		$newFormGoatFeatureQ2 = $newFormGoat->andGrammaticalFeature( 'Q2' );
		$newFormGoatFeatureQ1andQ2 = $newFormGoat
			->andGrammaticalFeature( 'Q1' )
			->andGrammaticalFeature( 'Q2' );

		$withForm1 = $this->newLexemeWithForm( $newFormGoatFeatureQ1->build() );
		$withForm1Again = $this->newLexemeWithForm( $newFormGoatFeatureQ1->build() );
		$withFormAndNoFeature = $this->newLexemeWithForm( $newFormGoat->build() );
		$withFormAndFeatureQ1 = $this->newLexemeWithForm( $newFormGoatFeatureQ1->build() );
		$withFormAndFeatureQ2 = $this->newLexemeWithForm( $newFormGoatFeatureQ2->build() );
		$withFormAndFeatureQ1andQ2 = $this->newLexemeWithForm( $newFormGoatFeatureQ1andQ2->build() );

		return [
			'true, empty' => [
				true,
				$empty,
				new Lexeme()
			],
			'true, same id' => [
				true,
				new Lexeme( new LexemeId( 'L1' ) ),
				new Lexeme( new LexemeId( 'L1' ) )
			],
			'true, different id' => [
				true,
				new Lexeme( new LexemeId( 'L1' ) ),
				new Lexeme( new LexemeId( 'L2' ) )
			],
			'true, no id' => [
				true,
				new Lexeme( new LexemeId( 'L1' ) ),
				$empty
			],
			'true, same object' => [
				true,
				$empty,
				$empty
			],
			'true, same statements' => [
				true,
				$withStatement,
				clone $withStatement
			],
			'true, same forms' => [
				true,
				$withForm1,
				$withForm1Again
			],
			'false, differing form feature 1->2' => [
				false, $withFormAndFeatureQ1, $withFormAndFeatureQ2
			],
			'false, differing form feature 1->1,2 ' => [
				false, $withFormAndFeatureQ1, $withFormAndFeatureQ1andQ2
			],
			'false, differing form feature null->1 ' => [
				false, $withFormAndNoFeature, $withFormAndFeatureQ1
			],
			'false, differing form feature 1->null ' => [
				false, $withFormAndFeatureQ1, $withFormAndNoFeature
			],
		];
	}

	/**
	 * @dataProvider provideTestEquals
	 */
	public function testEquals( $expected, Lexeme $a, Lexeme $b ) {
		$this->assertSame( $expected, $a->equals( $b ) );
	}

	public function testEqualLemmas() {
		$lexeme = new Lexeme();
		$lemmas = new TermList( [ new Term( 'es', 'Barcelona' ) ] );
		$lexeme->setLemmas( $lemmas );
		$this->assertFalse( $lexeme->getLemmas()->equals( null ) );
	}

	public function differentLexemesProvider() {
		$withStatement1 = new Lexeme();
		$withStatement1->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$withStatement2 = new Lexeme();
		$withStatement2->getStatements()->addNewStatement( new PropertyNoValueSnak( 24 ) );

		$lemmas1 = new TermList( [ new Term( 'fa', 'Shiraz' ) ] );
		$lemmas2 = new TermList( [ new Term( 'fa', 'Tehran' ) ] );

		$lexicalCategory1 = new ItemId( 'Q2' );
		$lexicalCategory2 = new ItemId( 'Q4' );

		$language1 = new ItemId( 'Q3' );
		$language2 = new ItemId( 'Q5' );

		$newLexeme = NewLexeme::create();
		$lexemeWithInitialFormCounter = $newLexeme->build();
		$lexemeWithChangedFormCounter = $newLexeme->withForm( NewForm::havingId( 'F1' ) )->build();
		$lexemeWithChangedFormCounter->removeForm( new FormId( 'L1-F1' ) );

		return [
			'null' => [
				new Lexeme(),
				null
			],
			'item' => [
				new Lexeme(),
				new Item()
			],
			'different statements' => [
				$withStatement1,
				$withStatement2
			],
			'different lemmas' => [
				new Lexeme( new LexemeId( 'l1' ), $lemmas1 ),
				new Lexeme( new LexemeId( 'l1' ), $lemmas2 ),
			],
			'different lexical categories' => [
				new Lexeme( new LexemeId( 'l1' ), null, $lexicalCategory1 ),
				new Lexeme( new LexemeId( 'l1' ), null, $lexicalCategory2 ),
			],
			'different languages' => [
				new Lexeme( new LexemeId( 'l2' ), null, null, $language1 ),
				new Lexeme( new LexemeId( 'l2' ), null, null, $language2 ),
			],
			'different Form set' => [
				new Lexeme( new LexemeId( 'L1' ) ),
				new Lexeme(
					new LexemeId( 'L1' ),
					null,
					null,
					null,
					null,
					2,
					new FormSet( [ NewForm::havingId( 'F1' )->build() ] )
				),
			],
			'different internal form index counter state' => [
				$lexemeWithInitialFormCounter,
				$lexemeWithChangedFormCounter,
			]
		];
	}

	/**
	 * @dataProvider differentLexemesProvider
	 */
	public function testNotEquals( Lexeme $a, $b ) {
		$this->assertFalse( $a->equals( $b ) );
	}

	public function testCopyEmptyEquals() {
		$lexeme = new Lexeme();

		$this->assertEquals( $lexeme, $lexeme->copy() );
	}

	public function testCopyWithIdEquals() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );

		$this->assertEquals( $lexeme, $lexeme->copy() );
	}

	public function testCopyWithContentEquals() {
		$lemmas = new TermList( [ new Term( 'de', 'Cologne' ) ] );
		$lexicalCategory = new ItemId( 'Q2' );
		$language = new ItemId( 'Q73' );
		$lexeme = new Lexeme( new LexemeId( 'L1' ), $lemmas, $lexicalCategory, $language );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertEquals( $lexeme, $lexeme->copy() );
	}

	public function testCopyObjectReferences() {
		$id = new LexemeId( 'L1' );
		$statements = new StatementList();

		$lexeme = new Lexeme( $id, null, null, null, $statements );
		$copy = $lexeme->copy();

		$this->assertSame( $id, $copy->getId() );
		$this->assertNotSame( $statements, $copy->getStatements() );
	}

	public function testCopyModification() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$lexeme->setLanguage( new ItemId( 'Q1' ) );
		$lexeme->setLexicalCategory( new ItemId( 'Q1' ) );
		$lexeme->getLemmas()->setTextForLanguage( 'en', 'orig' );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$formId = new FormId( 'L1-F' . $lexeme->getNextFormId() );
		$lexeme->addForm( new TermList( [ new Term( 'en', 'orig-form' ) ] ), [ new ItemId( 'Q1' ) ] );
		// Make sure we have the correct FormId for the form that we added (will throw otherwise)
		$lexeme->getForm( $formId );

		$copy = $lexeme->copy();

		$copy->setId( new LexemeId( 'L2' ) );
		$copy->setLanguage( new ItemId( 'Q2' ) );
		$copy->setLexicalCategory( new ItemId( 'Q2' ) );
		$copy->getLemmas()->setTextForLanguage( 'en', 'copy' );
		$copy->getStatements()->addNewStatement( new PropertyNoValueSnak( 24 ) );
		$copy->getStatements()->getFirstStatementWithGuid( null )->setRank(
			Statement::RANK_DEPRECATED
		);
		$copy->removeForm( $formId );
		// TODO test senses here once appropriate

		$this->assertSame( 'L1', $lexeme->getId()->getSerialization() );
		$this->assertSame( 'Q1', $lexeme->getLanguage()->getSerialization() );
		$this->assertSame( 'Q1', $lexeme->getLexicalCategory()->getSerialization() );
		$this->assertSame( 'orig', $lexeme->getLemmas()->getByLanguage( 'en' )->getText() );
		$this->assertCount( 1, $lexeme->getStatements() );
		$this->assertSame(
			Statement::RANK_NORMAL,
			$lexeme->getStatements()->getFirstStatementWithGuid( null )->getRank()
		);
		$this->assertCount( 0, $copy->getForms() );
		$this->assertCount( 1, $lexeme->getForms() );
	}

	public function testCopy_FormSetIsCopied() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexemeCopy = $lexeme->copy();

		$lexemeCopy->addForm( new TermList( [ new Term( 'en', 'goat' ) ] ), [] );

		$this->assertCount( 1, $lexemeCopy->getForms()->toArray() );
		$this->assertEmpty( $lexeme->getForms()->toArray() );
	}

	public function testCopy_SensesAreCopied() {
		$lexeme = NewLexeme::havingId( 'L1' )
			->withSense( NewSense::havingId( 'S1' ) )
			->build();
		$lexemeCopy = $lexeme->copy();

		$initialSense = $lexeme->getSenses()[0];
		$copySense = $lexemeCopy->getSenses()[0];

		$this->assertNotSame( $initialSense, $copySense );
	}

	public function testSetLemmas() {
		$id = new LexemeId( 'L1' );
		$lemmas = new TermList( [ new Term( 'fa', 'Karaj' ) ] );

		$lexeme = new Lexeme( $id );
		$lexeme->setLemmas( $lemmas );

		$this->assertSame( $lemmas, $lexeme->getLemmas() );
	}

	public function testSetLexicalCategory() {
		$id = new LexemeId( 'L1' );
		$lexicalCategory = new ItemId( 'Q55' );

		$lexeme = new Lexeme( $id );
		$lexeme->setLexicalCategory( $lexicalCategory );

		$this->assertSame( $lexicalCategory, $lexeme->getLexicalCategory() );
	}

	public function testSetLanguage() {
		$id = new LexemeId( 'L2' );
		$language = new ItemId( 'Q44' );

		$lexeme = new Lexeme( $id );
		$lexeme->setLanguage( $language );

		$this->assertSame( $language, $lexeme->getLanguage() );
	}

	public function testAddForm_ReturnsANewFormWithProvidedParameters() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$newForm = $lexeme->addForm(
			new TermList( [ new Term( 'en', 'goat' ) ] ),
			[ new ItemId( 'Q1' ) ]
		);

		$this->assertEquals(
			new TermList( [ new Term( 'en', 'goat' ) ] ),
			$newForm->getRepresentations()
		);
		$this->assertEquals(
			[ new ItemId( 'Q1' ) ],
			$newForm->getGrammaticalFeatures()
		);
	}

	public function testAddForm_ReturnedFormIsAddedToTheLexeme() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$newForm = $lexeme->addForm(
			new TermList( [ new Term( 'en', 'goat' ) ] ),
			[]
		);

		$this->assertEquals( new FormSet( [ $newForm ] ), $lexeme->getForms() );
	}

	public function testAddFormTwoTimes_SecondFormHasAnIdWithNextNumber() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$newForm1 = $lexeme->addForm(
			new TermList( [ new Term( 'en', 'goat' ) ] ),
			[]
		);
		$newForm2 = $lexeme->addForm(
			new TermList( [ new Term( 'en', 'goat1' ) ] ),
			[]
		);

		$this->assertEquals( new FormId( 'L1-F1' ), $newForm1->getId() );
		$this->assertEquals( new FormId( 'L1-F2' ), $newForm2->getId() );
	}

	public function testRemoveAForm() {
		$lexeme = NewLexeme::havingForm( NewForm::havingId( 'F1' ) )->build();

		$lexeme->removeForm( new FormId( 'L1-F1' ) );

		$this->assertEquals( [], $lexeme->getForms()->toArray() );
	}

	public function testAddOrUpdateForm_updatedFormReference() {
		$lexeme = NewLexeme::havingId( new LexemeId( 'L7' ) )
			->withForm( NewForm::havingId( 'F1' ) )
			->build();

		$newForm = NewForm::havingId( 'F1' )->andLexeme( 'L7' )->build();
		$lexeme->addOrUpdateForm( $newForm );

		$this->assertSame( [ $newForm ], $lexeme->getForms()->toArray() );
	}

	public function testGetForm_LexemeHaveFormWithThatId_ReturnsThatForm() {
		$lexeme = NewLexeme::havingForm( NewForm::havingId( 'F1' ) )->build();

		$this->assertInstanceOf( Form::class, $lexeme->getForm( new FormId( 'L1-F1' ) ) );
	}

	public function testGetForm_LexemeDoesntHaveFormWithThatId_ThrowsAnException() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->setExpectedException( \OutOfRangeException::class );
		$lexeme->getForm( new FormId( 'L1-F1' ) );
	}

	public function testPatch_IncreaseNextFormIdTo_GivenLexemWithGreaterId_Increases() {
		$lexemeWithoutForm = NewLexeme::create()->build();
		$this->assertEquals( 1, $lexemeWithoutForm->getNextFormId() );

		$lexemeWithoutForm->patch(
			function ( LexemePatchAccess $patchAccess ) {
				$patchAccess->increaseNextFormIdTo( 2 );
			}
		);

		$this->assertEquals( 2, $lexemeWithoutForm->getNextFormId() );
	}

	public function testPatch_IncreaseNextFormIdTo_AddFormWithTooBigId_LexemesStateIsUnchanged() {
		$lexeme = NewLexeme::create()->build();
		$initialLexeme = clone $lexeme;
		$newForm = NewForm::havingId( 'F3' )->build();

		try {
			$lexeme->patch(
				function ( LexemePatchAccess $patchAccess ) use ( $newForm ) {
					$patchAccess->increaseNextFormIdTo( 2 );
					$patchAccess->addForm( $newForm );
				}
			);
			$this->fail( "patch() should have failed" );
		} catch ( \Exception $e ) {
			// ignore
		}
		$this->assertTrue( $lexeme->equals( $initialLexeme ), "Lexeme's state is changed" );
	}

	public function testPatch_AddAFormThatAlreadyExisted_AddsAForm() {
		$lexeme = NewLexeme::havingForm( NewForm::havingId( 'F1' ) )->build();
		$lexeme->removeForm( new FormId( 'L1-F1' ) );
		$restoredForm = NewForm::havingId( 'F1' )->build();

		$lexeme->patch(
			function ( LexemePatchAccess $patchAccess ) use ( $restoredForm ) {
				$patchAccess->addForm( $restoredForm );
			}
		);

		$this->assertEquals( new FormSet( [ $restoredForm ] ), $lexeme->getForms() );
	}

	public function testPatch_CannotAddAFromToLexemePatchAccessAfterPatchingIsFinished() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = NewForm::any()->build();

		/** @var LexemePatchAccess $patchAccessFromOutside */
		$patchAccessFromOutside = null;
		$lexeme->patch(
			function ( LexemePatchAccess $patchAccess ) use ( &$patchAccessFromOutside ) {
				$patchAccessFromOutside = $patchAccess;
			}
		);

		$this->setExpectedException( \Exception::class );
		$patchAccessFromOutside->addForm( $form );
	}

	public function testPatch_CannotAddAFromToLexemePatchAccessIfPatchingHasFailed() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = NewForm::any()->build();

		/** @var LexemePatchAccess $patchAccessFromOutside */
		$patchAccessFromOutside = null;
		try {
			$lexeme->patch(
				function ( LexemePatchAccess $patchAccess ) use ( &$patchAccessFromOutside ) {
					$patchAccessFromOutside = $patchAccess;
					throw new \Exception();
				}
			);
			$this->fail( "patch() should have failed" );
		} catch ( \Exception $e ) {
			// ignore
		}
		$this->setExpectedException( \Exception::class );
		$patchAccessFromOutside->addForm( $form );
	}

	public function testPatch_CannotAddAFromIfLexemeAlreadyHasAFormWithTheSameIdIs() {
		$existingForm = NewForm::havingId( 'F1' )->build();
		$lexeme = NewLexeme::havingForm( $existingForm )->build();
		$newForm = NewForm::havingId( 'F1' )->build();

		$this->setExpectedException( \Exception::class );
		$lexeme->patch(
			function ( LexemePatchAccess $patchAccess ) use ( $newForm ) {
				$patchAccess->addForm( $newForm );
			}
		);
	}

	public function testPatch_CannotAddAFromWithIdThatIsBiggerThanLexemeNextFormIdCounter() {
		$lexeme = NewLexeme::create()->build();
		$newForm = NewForm::havingId( 'F1' )->build();

		$this->assertEquals( 1, $lexeme->getNextFormId() );
		$this->setExpectedException( \Exception::class );
		$lexeme->patch(
			function ( LexemePatchAccess $patchAccess ) use ( $newForm ) {
				$patchAccess->addForm( $newForm );
			}
		);
	}

	public function testPatch_FormAdditionFails_LexemesStateIsUnchanged() {
		$lexeme = NewLexeme::create()->build();
		$initialLexeme = clone $lexeme;
		$newForm = NewForm::havingId( 'F1' )->build();

		try {
			$lexeme->patch(
				function ( LexemePatchAccess $patchAccess ) use ( $newForm ) {
					$patchAccess->addForm( $newForm );
				}
			);
			$this->fail( "patch() should have failed" );
		} catch ( \Exception $e ) {
			// ignore
		}
		$this->assertTrue( $lexeme->equals( $initialLexeme ), "Lexeme's state is changed" );
	}

	public function testLexemeWithNoIdIsNotSufficientlyInitialized() {
		$lexeme = new Lexeme(
			null,
			new TermList( [ new Term( 'en', 'test' ) ] ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' )
		);

		$this->assertFalse( $lexeme->isSufficientlyInitialized() );
	}

	public function testLexemeWithNoLemmaIsNotSufficientlyInitialized() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			null,
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' )
		);

		$this->assertFalse( $lexeme->isSufficientlyInitialized() );
	}

	public function testLexemeWithEmptyLemmaListIsNotSufficientlyInitialized() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList(),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' )
		);

		$this->assertFalse( $lexeme->isSufficientlyInitialized() );
	}

	public function testLexemeWithNoLexicalCategoryIsNotSufficientlyInitialized() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'test' ) ] ),
			null,
			new ItemId( 'Q2' )
		);

		$this->assertFalse( $lexeme->isSufficientlyInitialized() );
	}

	public function testLexemeWithNoLanguageIsNotSufficientlyInitialized() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'test' ) ] ),
			new ItemId( 'Q2' ),
			null
		);

		$this->assertFalse( $lexeme->isSufficientlyInitialized() );
	}

	public function testLexemeWithRequiredElementsIsSufficientlyInitialized() {
		$lexeme = new Lexeme(
			new LexemeId( 'L1' ),
			new TermList( [ new Term( 'en', 'test' ) ] ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q3' )
		);

		$this->assertTrue( $lexeme->isSufficientlyInitialized() );
	}

	public function testClearDoesNotResetFormIdCounter() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexeme->addForm( new TermList( [ new Term( 'en', 'foo' ) ] ), [] );

		$lexeme->clear();

		$this->assertTrue( $lexeme->isEmpty() );
		$this->assertSame( 2, $lexeme->getNextFormId() );
	}

	public function testClear_clearsLanguage() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexeme->clear();

		$this->assertTrue( $lexeme->isEmpty() );
		$this->setExpectedException( UnexpectedValueException::class );
		$lexeme->getLanguage();
	}

	public function testClear_clearsLexicalCategory() {
		$lexeme = NewLexeme::havingId( 'L2' )->build();
		$lexeme->clear();

		$this->assertTrue( $lexeme->isEmpty() );
		$this->setExpectedException( UnexpectedValueException::class );
		$lexeme->getLexicalCategory();
	}

	/**
	 * @dataProvider clearableProvider
	 */
	public function testClear( Lexeme $lexeme ) {
		$clone = $lexeme->copy();

		$lexeme->clear();

		$this->assertEquals( $clone->getId(), $lexeme->getId(), 'ids must be equal' );
		$this->assertTrue( $lexeme->isEmpty(), 'lexeme must be empty after clear' );
	}

	public function clearableProvider() {
		return [
			'empty' => [
				NewLexeme::havingId( 'L1' )->build(),
			],
			'with lemmas' => [
				NewLexeme::havingId( 'L2' )
					->withLemma( 'en', 'foo' )
					->build(),
			],
			'with statements' => [
				NewLexeme::havingId( 'L3' )
					->withStatement( new PropertyNoValueSnak( 42 ) )
					->build(),
			],
			'with forms' => [
				NewLexeme::havingId( 'L4' )
					->withForm( NewForm::any() )
					->build(),
			],
			'with senses' => [
				NewLexeme::havingId( 'L5' )
					->withSense( NewSense::havingId( 'S1' ) )
					->build(),
			],
		];
	}

}

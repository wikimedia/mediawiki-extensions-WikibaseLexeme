<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit\Framework\TestCase;
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
 * @license GPL-2.0+
 */
class LexemeTest extends TestCase {

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

	public function equalLexemesProvider() {
		$empty = new Lexeme();

		$withStatement = new Lexeme();
		$withStatement->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$form = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'goat' )
			->andGrammaticalFeature( 'Q1' );

		$withForm1 = new Lexeme(
			new LexemeId( 'L1' ), null, null, null, null, 2, new FormSet( [ $form->build() ] )
		);
		$withForm2 = new Lexeme(
			new LexemeId( 'L1' ), null, null, null, null, 2, new FormSet( [ $form->build() ] )
		);

		return [
			'empty' => [
				$empty,
				new Lexeme()
			],
			'same id' => [
				new Lexeme( new LexemeId( 'L1' ) ),
				new Lexeme( new LexemeId( 'L1' ) )
			],
			'different id' => [
				new Lexeme( new LexemeId( 'L1' ) ),
				new Lexeme( new LexemeId( 'L2' ) )
			],
			'no id' => [
				new Lexeme( new LexemeId( 'L1' ) ),
				$empty
			],
			'same object' => [
				$empty,
				$empty
			],
			'same statements' => [
				$withStatement,
				clone $withStatement
			],
			'same forms' => [
				$withForm1,
				$withForm2
			],
		];
	}

	/**
	 * @dataProvider equalLexemesProvider
	 */
	public function testEquals( Lexeme $a, Lexeme $b ) {
		$this->assertTrue( $a->equals( $b ) );
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
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$copy = $lexeme->copy();

		$copy->setId( new LexemeId( 'L2' ) );
		$copy->getStatements()->addNewStatement( new PropertyNoValueSnak( 24 ) );
		$copy->getStatements()->getFirstStatementWithGuid( null )->setRank(
			Statement::RANK_DEPRECATED
		);

		$this->assertSame( 'L1', $lexeme->getId()->getSerialization() );
		$this->assertCount( 1, $lexeme->getStatements() );
		$this->assertSame(
			Statement::RANK_NORMAL,
			$lexeme->getStatements()->getFirstStatementWithGuid( null )->getRank()
		);
	}

	public function testCopy_FormSetIsCopied() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexemeCopy = $lexeme->copy();

		$lexemeCopy->addForm( new TermList( [ new Term( 'en', 'goat' ) ] ), [] );

		$this->assertCount( 1, $lexemeCopy->getForms()->toArray() );
		$this->assertEmpty( $lexeme->getForms()->toArray() );
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

	public function testHasForm_LexemeDoesnHaveForms_ReturnsFalse() {
		$lexeme = NewLexeme::create()->build();

		$this->assertFalse( $lexeme->hasForm( new FormId( 'L1-F1' ) ) );
	}

	public function testHasForm_LexemeHaveFormWithThatId_ReturnsTrue() {
		$lexeme = NewLexeme::havingForm( NewForm::havingId( 'F1' ) )->build();

		$this->assertTrue( $lexeme->hasForm( new FormId( 'L1-F1' ) ) );
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

}

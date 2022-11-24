<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use InvalidArgumentException;
use LogicException;
use MediaWikiUnitTestCase;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\LexemePatchAccess;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\Model\SenseSet;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\Lexeme
 *
 * @license GPL-2.0-or-later
 */
class LexemeTest extends MediaWikiUnitTestCase {

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

		$this->expectException( UnexpectedValueException::class );
		$lexeme->getLexicalCategory();
	}

	public function testUninitializedLanguage() {
		$lexeme = new Lexeme();

		$this->expectException( UnexpectedValueException::class );
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
		// FIXME: This behaviour seems wrong. Should probably be changed
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$id = new LexemeId( 'L2' );
		$lexeme->setId( $id );

		$this->assertSame( $id, $lexeme->getId() );
	}

	public function testCanNotCreateWithNonIntNextFormId() {
		$this->expectException( \Exception::class );
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
		$this->expectException( \Exception::class );
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
		$this->expectException( \Exception::class );
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
		$this->expectException( \Exception::class );
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
		$this->expectException( \Exception::class );
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
		$this->expectException( \Exception::class );
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
		$this->expectException( \Exception::class );
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
		$this->expectException( \Exception::class );
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

		$this->expectException( InvalidArgumentException::class );
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
			new SenseSet( [ NewSense::havingId( 'S1' )->build() ] )
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

		$goatSense = NewSense::havingId( 'S1' );
		$goatSense->withGloss( 'en', 'goat' );
		$withSense = NewLexeme::havingId( 'L1' )
			->withSense( $goatSense->build() )
			->build();

		$catSense = NewSense::havingId( 'S2' );
		$catSense->withGloss( 'en', 'cat' );
		$withSenseButDifferentCounter = NewLexeme::havingId( 'L1' )
			->withSense( $goatSense->build() )
			->withSense( $catSense->build() )
			->build();
		$withSenseButDifferentCounter->removeSense( new SenseId( 'L1-S2' ) );

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
			'true, same senses' => [
				true,
				$withSense,
				clone $withSense
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
			'false, differing sense' => [
				false, $withSense, $empty
			],
			'false, differing sense counter' => [
				false, $withSense, $withSenseButDifferentCounter
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
		$blankForm = new BlankForm();
		$blankForm->getRepresentations()->setTextForLanguage( 'en', 'orig-form' );
		$blankForm->setGrammaticalFeatures( [ new ItemId( 'Q1' ) ] );
		$lexeme->addOrUpdateForm( $blankForm );
		$formId = $blankForm->getId();
		$blankSense = new BlankSense();
		$blankSense->getGlosses()->setTextForLanguage( 'en', 'orig-sense' );
		$lexeme->addOrUpdateSense( $blankSense );
		$senseId = $blankSense->getId();

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
		$copy->removeSense( $senseId );

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
		$this->assertCount( 0, $copy->getSenses() );
		$this->assertCount( 1, $lexeme->getSenses() );
	}

	public function testCopy_FormSetIsCopied() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexemeCopy = $lexeme->copy();

		$blankForm = new BlankForm();
		$blankForm->getRepresentations()->setTextForLanguage( 'en', 'goat' );
		$lexemeCopy->addOrUpdateForm( $blankForm );

		$this->assertCount( 1, $lexemeCopy->getForms()->toArray() );
		$this->assertSame( [], $lexeme->getForms()->toArray() );
	}

	public function testCopy_SenseSetIsCopied() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexemeCopy = $lexeme->copy();

		$blankSense = new BlankSense();
		$blankSense->getGlosses()->setTextForLanguage( 'en', 'animal' );
		$lexemeCopy->addOrUpdateSense( $blankSense );

		$this->assertCount( 1, $lexemeCopy->getSenses()->toArray() );
		$this->assertSame( [], $lexeme->getSenses()->toArray() );
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

	public function testSetLexicalCategoryNull() {
		$id = new LexemeId( 'L1' );

		$lexeme = new Lexeme( $id );
		$lexeme->setLexicalCategory( null );

		$this->expectException( UnexpectedValueException::class );
		$lexeme->getLexicalCategory();
	}

	public function testSetLanguage() {
		$id = new LexemeId( 'L2' );
		$language = new ItemId( 'Q44' );

		$lexeme = new Lexeme( $id );
		$lexeme->setLanguage( $language );

		$this->assertSame( $language, $lexeme->getLanguage() );
	}

	public function testSetLanguageNull() {
		$id = new LexemeId( 'L1' );

		$lexeme = new Lexeme( $id );
		$lexeme->setLanguage( null );

		$this->expectException( UnexpectedValueException::class );
		$lexeme->getLanguage();
	}

	public function testAddOrUpdateSenseTwice_secondReturnedSenseHasIncrementedId() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$blankSense = new BlankSense();
		$blankSense->getGlosses()->setTextForLanguage( 'en', 'color' );
		$lexeme->addOrUpdateSense( $blankSense );

		$blankSense2 = new BlankSense();
		$blankSense2->getGlosses()->setTextForLanguage( 'en-gb', 'colour' );
		$lexeme->addOrUpdateSense( $blankSense2 );

		$this->assertEquals( 'L1-S1', $blankSense->getId()->getSerialization() );
		$this->assertEquals( 'L1-S2', $blankSense2->getId()->getSerialization() );
	}

	public function testRemoveAForm() {
		$lexeme = NewLexeme::havingForm( NewForm::havingId( 'F1' ) )->build();

		$lexeme->removeForm( new FormId( 'L1-F1' ) );

		$this->assertEquals( [], $lexeme->getForms()->toArray() );
	}

	public function testRemoveASense() {
		$lexeme = NewLexeme::havingSense( NewSense::havingId( 'S1' ) )->build();

		$lexeme->removeSense( new SenseId( 'L1-S1' ) );

		$this->assertEquals( [], $lexeme->getSenses()->toArray() );
	}

	public function testAddOrUpdateFormOnLexemeWithoutId_throwsException() {
		$lexeme = new Lexeme();

		$newForm = NewForm::havingId( 'F1' )->build();
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Can not add forms to a lexeme with no ID' );
		$lexeme->addOrUpdateForm( $newForm );
	}

	public function testAddOrUpdateForm_updatesFormReference() {
		$lexeme = NewLexeme::havingId( new LexemeId( 'L7' ) )
			->withForm( NewForm::havingId( 'F1' ) )
			->build();

		$newForm = NewForm::havingId( 'F1' )->andLexeme( 'L7' )->build();
		$lexeme->addOrUpdateForm( $newForm );

		$this->assertSame( [ $newForm ], $lexeme->getForms()->toArray() );
	}

	public function testAddOrUpdateForm_addsForm() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$blankForm = new BlankForm();
		$representation = new Term( 'en', 'representation' );
		$blankForm->setRepresentations( new TermList( [ $representation ] ) );

		$lexeme->addOrUpdateForm( $blankForm );

		$this->assertSame( 'L1-F1', $blankForm->getId()->getSerialization() );
		$this->assertSame( $representation, $blankForm->getRepresentations()->getByLanguage( 'en' ) );
	}

	public function testAddOrUpdateFormWithDummyFormId_addsFormAndTendsToNextFormId() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexemeOtherReference = NewLexeme::havingId( 'L1' )->build();

		$blankForm = new BlankForm();
		$representation = new Term( 'en', 'representation' );
		$blankForm->setRepresentations( new TermList( [ $representation ] ) );

		$lexeme->addOrUpdateForm( $blankForm );
		$lexemeOtherReference->addOrUpdateForm( $blankForm );

		$this->assertSame( $lexeme->getNextFormId(), $lexemeOtherReference->getNextFormId() );
		$this->assertTrue(
			$lexemeOtherReference->getForms()->getById( new FormId( 'L1-F1' ) )->equals(
				$lexeme->getForms()->getById( new FormId( 'L1-F1' ) )
			)
		);
	}

	public function testAddOrUpdateFormWithFormWithTooHighId_throwsException() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = NewForm::havingLexeme( 'L1' )->andId( 'F200' )->build();

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( '$nextFormId must always be greater than the number of Forms.' );
		$lexeme->addOrUpdateForm( $form );
	}

	public function testAddOrUpdateFormTwice_secondFormHasIncrementedId() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$blankForm1 = new BlankForm();
		$representation = new Term( 'en', 'color' );
		$blankForm1->setRepresentations( new TermList( [ $representation ] ) );
		$lexeme->addOrUpdateForm( $blankForm1 );

		$blankForm2 = new BlankForm();
		$representation = new Term( 'en-gb', 'colour' );
		$blankForm2->setRepresentations( new TermList( [ $representation ] ) );
		$lexeme->addOrUpdateForm( $blankForm2 );

		$this->assertEquals( 'L1-F1', $blankForm1->getId()->getSerialization() );
		$this->assertEquals( 'L1-F2', $blankForm2->getId()->getSerialization() );
	}

	public function testGivenTheSameFormTwice_addOrUpdateFormOnlyAddsOnce() {
		$form = new BlankForm();
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$lexeme->addOrUpdateForm( $form );
		$lexeme->addOrUpdateForm( $form );

		$this->assertCount( 1, $lexeme->getForms() );
	}

	public function testAddOrUpdateSense_updatedSenseReference() {
		$lexeme = NewLexeme::havingId( new LexemeId( 'L7' ) )
			->withSense( NewSense::havingId( 'S1' ) )
			->build();

		$newSense = new Sense( new SenseId( 'L7-S1' ), new TermList() );
		$lexeme->addOrUpdateSense( $newSense );

		$this->assertSame( [ $newSense ], $lexeme->getSenses()->toArray() );
	}

	public function testAddOrUpdateSenseOnLexemeWithoutId_throwsException() {
		$lexeme = new Lexeme();

		$newSense = NewSense::havingId( 'S1' )->build();
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Cannot add sense to a lexeme with no ID' );
		$lexeme->addOrUpdateSense( $newSense );
	}

	public function testAddOrUpdateSense_addsSense() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$blankSense = new BlankSense();

		$lexeme->addOrUpdateSense( $blankSense );

		$this->assertSame( 'L1-S1', $blankSense->getId()->getSerialization() );
	}

	public function testGetForm_LexemeHaveFormWithThatId_ReturnsThatForm() {
		$lexeme = NewLexeme::havingForm( NewForm::havingId( 'F1' ) )->build();

		$this->assertInstanceOf( Form::class, $lexeme->getForm( new FormId( 'L1-F1' ) ) );
	}

	public function testGetForm_LexemeDoesntHaveFormWithThatId_ThrowsAnException() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->expectException( \OutOfRangeException::class );
		$lexeme->getForm( new FormId( 'L1-F1' ) );
	}

	public function testGetSense_LexemeHasSenseWithThatId_ReturnsThatSense() {
		$lexeme = NewLexeme::havingSense( NewSense::havingId( 'S1' ) )->build();

		$this->assertInstanceOf( Sense::class, $lexeme->getSense( new SenseId( 'L1-S1' ) ) );
	}

	public function testGetSense_LexemeDoesntHaveSenseWithThatId_ThrowsAnException() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$this->expectException( \OutOfRangeException::class );
		$lexeme->getSense( new SenseId( 'L1-S1' ) );
	}

	public function testPatch_IncreaseNextFormIdTo_GivenLexemWithGreaterId_Increases() {
		$lexemeWithoutForm = NewLexeme::create()->build();
		$this->assertSame( 1, $lexemeWithoutForm->getNextFormId() );

		$lexemeWithoutForm->patch(
			static function ( LexemePatchAccess $patchAccess ) {
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
				static function ( LexemePatchAccess $patchAccess ) use ( $newForm ) {
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
			static function ( LexemePatchAccess $patchAccess ) use ( $restoredForm ) {
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
			static function ( LexemePatchAccess $patchAccess ) use ( &$patchAccessFromOutside ) {
				$patchAccessFromOutside = $patchAccess;
			}
		);

		$this->expectException( \Exception::class );
		$patchAccessFromOutside->addForm( $form );
	}

	public function testPatch_CannotAddAFromToLexemePatchAccessIfPatchingHasFailed() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$form = NewForm::any()->build();

		/** @var LexemePatchAccess $patchAccessFromOutside */
		$patchAccessFromOutside = null;
		try {
			$lexeme->patch(
				static function ( LexemePatchAccess $patchAccess ) use ( &$patchAccessFromOutside ) {
					$patchAccessFromOutside = $patchAccess;
					throw new \Exception();
				}
			);
			$this->fail( "patch() should have failed" );
		} catch ( \Exception $e ) {
			// ignore
		}
		$this->expectException( \Exception::class );
		$patchAccessFromOutside->addForm( $form );
	}

	public function testPatch_CannotAddAFromIfLexemeAlreadyHasAFormWithTheSameIdIs() {
		$existingForm = NewForm::havingId( 'F1' )->build();
		$lexeme = NewLexeme::havingForm( $existingForm )->build();
		$newForm = NewForm::havingId( 'F1' )->build();

		$this->expectException( \Exception::class );
		$lexeme->patch(
			static function ( LexemePatchAccess $patchAccess ) use ( $newForm ) {
				$patchAccess->addForm( $newForm );
			}
		);
	}

	public function testPatch_CannotAddAFromWithIdThatIsBiggerThanLexemeNextFormIdCounter() {
		$lexeme = NewLexeme::create()->build();
		$newForm = NewForm::havingId( 'F1' )->build();

		$this->assertSame( 1, $lexeme->getNextFormId() );
		$this->expectException( \Exception::class );
		$lexeme->patch(
			static function ( LexemePatchAccess $patchAccess ) use ( $newForm ) {
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
				static function ( LexemePatchAccess $patchAccess ) use ( $newForm ) {
					$patchAccess->addForm( $newForm );
				}
			);
			$this->fail( "patch() should have failed" );
		} catch ( \Exception $e ) {
			// ignore
		}
		$this->assertTrue( $lexeme->equals( $initialLexeme ), "Lexeme's state is changed" );
	}

	public function testPatch_IncreaseNextSenseIdTo_GivenLexemeWithGreaterId_Increases() {
		$lexemeWithoutSense = NewLexeme::create()->build();
		$this->assertSame( 1, $lexemeWithoutSense->getNextSenseId() );

		$lexemeWithoutSense->patch(
			static function ( LexemePatchAccess $patchAccess ) {
				$patchAccess->increaseNextSenseIdTo( 2 );
			}
		);

		$this->assertEquals( 2, $lexemeWithoutSense->getNextSenseId() );
	}

	public function testPatch_IncreaseNextSenseIdTo_AddSenseWithTooBigId_LexemesStateIsUnchanged() {
		$lexeme = NewLexeme::create()->build();
		$initialLexeme = clone $lexeme;
		$newSense = NewSense::havingId( 'S3' )->build();

		try {
			$lexeme->patch(
				static function ( LexemePatchAccess $patchAccess ) use ( $newSense ) {
					$patchAccess->increaseNextSenseIdTo( 2 );
					$patchAccess->addSense( $newSense );
				}
			);
			$this->fail( "patch() should have failed" );
		} catch ( \Exception $e ) {
			// ignore
		}
		$this->assertTrue( $lexeme->equals( $initialLexeme ), "Lexeme's state is changed" );
	}

	public function testPatch_AddASenseThatAlreadyExisted_AddsASense() {
		$lexeme = NewLexeme::havingSense( NewSense::havingId( 'S1' ) )->build();
		$lexeme->removeSense( new SenseId( 'L1-S1' ) );
		$restoredSense = NewSense::havingId( 'S1' )->build();

		$lexeme->patch(
			static function ( LexemePatchAccess $patchAccess ) use ( $restoredSense ) {
				$patchAccess->addSense( $restoredSense );
			}
		);

		$this->assertEquals( new SenseSet( [ $restoredSense ] ), $lexeme->getSenses() );
	}

	public function testPatch_CannotAddASenseToLexemePatchAccessAfterPatchingIsFinished() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$newSense = NewSense::havingId( 'S1' )->build();

		/** @var LexemePatchAccess $patchAccessFromOutside */
		$patchAccessFromOutside = null;
		$lexeme->patch(
			static function ( LexemePatchAccess $patchAccess ) use ( &$patchAccessFromOutside ) {
				$patchAccessFromOutside = $patchAccess;
			}
		);

		$this->expectException( \Exception::class );
		$patchAccessFromOutside->addSense( $newSense );
	}

	public function testPatch_CannotAddASenseToLexemePatchAccessIfPatchingHasFailed() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$newSense = NewSense::havingId( 'S1' )->build();

		/** @var LexemePatchAccess $patchAccessFromOutside */
		$patchAccessFromOutside = null;
		try {
			$lexeme->patch(
				static function ( LexemePatchAccess $patchAccess ) use ( &$patchAccessFromOutside ) {
					$patchAccessFromOutside = $patchAccess;
					throw new \Exception();
				}
			);
			$this->fail( "patch() should have failed" );
		} catch ( \Exception $e ) {
			// ignore
		}
		$this->expectException( \Exception::class );
		$patchAccessFromOutside->addSense( $newSense );
	}

	public function testPatch_CannotAddASenseIfLexemeAlreadyHasASenseWithTheSameId() {
		$existingSense = NewSense::havingId( 'S1' )->build();
		$lexeme = NewLexeme::havingSense( $existingSense )->build();
		$newSense = NewSense::havingId( 'S1' )->build();

		$this->expectException( \Exception::class );
		$lexeme->patch(
			static function ( LexemePatchAccess $patchAccess ) use ( $newSense ) {
				$patchAccess->addSense( $newSense );
			}
		);
	}

	public function testPatch_CannotAddASenseWithIdThatIsBiggerThanLexemeNextSenseIdCounter() {
		$lexeme = NewLexeme::create()->build();
		$newSense = NewSense::havingId( 'S1' )->build();

		$this->assertSame( 1, $lexeme->getNextSenseId() );
		$this->expectException( \Exception::class );
		$lexeme->patch(
			static function ( LexemePatchAccess $patchAccess ) use ( $newSense ) {
				$patchAccess->addSense( $newSense );
			}
		);
	}

	public function testPatch_SenseAdditionFails_LexemesStateIsUnchanged() {
		$lexeme = NewLexeme::create()->build();
		$initialLexeme = clone $lexeme;
		$newSense = NewSense::havingId( 'S1' )->build();

		try {
			$lexeme->patch(
				static function ( LexemePatchAccess $patchAccess ) use ( $newSense ) {
					$patchAccess->addSense( $newSense );
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

		$form = new BlankForm();
		$form->getRepresentations()->setTextForLanguage( 'en', 'foo' );

		$lexeme->addOrUpdateForm( $form );

		$lexeme->clear();

		$this->assertTrue( $lexeme->isEmpty() );
		$this->assertSame( 2, $lexeme->getNextFormId() );
	}

	public function testClearDoesNotResetSenseIdCounter() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();

		$sense = new BlankSense();
		$sense->getGlosses()->setTextForLanguage( 'en', 'foo' );

		$lexeme->addOrUpdateSense( $sense );

		$lexeme->clear();

		$this->assertTrue( $lexeme->isEmpty() );
		$this->assertSame( 2, $lexeme->getNextSenseId() );
	}

	public function testClear_clearsLanguage() {
		$lexeme = NewLexeme::havingId( 'L1' )->build();
		$lexeme->clear();

		$this->assertTrue( $lexeme->isEmpty() );
		$this->expectException( UnexpectedValueException::class );
		$lexeme->getLanguage();
	}

	public function testClear_clearsLexicalCategory() {
		$lexeme = NewLexeme::havingId( 'L2' )->build();
		$lexeme->clear();

		$this->assertTrue( $lexeme->isEmpty() );
		$this->expectException( UnexpectedValueException::class );
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

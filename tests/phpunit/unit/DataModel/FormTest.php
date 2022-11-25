<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use InvalidArgumentException;
use LogicException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Domain\DummyObjects\NullFormId;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\Form
 *
 * @license GPL-2.0-or-later
 */
class FormTest extends MediaWikiUnitTestCase {

	public function testCreateFormWithoutRepresentations_resultsInEmptyRepresentationList() {
		$form = new Form( new FormId( 'L1-F1' ), new TermList(), [] );

		$this->assertTrue( $form->getRepresentations()->isEmpty() );
	}

	public function testCreateFormWithOneRepresentation_CreatesIt() {
		$form = new Form(
			new FormId( 'L1-F1' ),
			new TermList( [ new Term( 'en', 'representation' ) ] ),
			[]
		);
		$this->assertCount( 1, $form->getRepresentations() );
	}

	public function testCreateForm_GrammaticalFeaturesIsNotAnArrayOfItemIds_ThrowsAnException() {
		$this->expectException( InvalidArgumentException::class );
		new Form(
			new FormId( 'L1-F1' ),
			new TermList( [ new Term( 'en', 'representation' ) ] ),
			[ 1 ]
		);
	}

	public function testSetGrammaticalFeatures_RemovesDuplicateItemIds() {
		$form = NewForm::havingId( 'F1' )->build();

		$form->setGrammaticalFeatures( [ new ItemId( 'Q1' ), new ItemId( 'Q1' ) ] );

		$this->assertEquals( [ new ItemId( 'Q1' ) ], $form->getGrammaticalFeatures() );
	}

	public function testSetGrammaticalFeatures_AlphabeticallySortsItemIdsByTheirSerialization() {
		$form = NewForm::havingId( 'F1' )->build();

		$form->setGrammaticalFeatures( [ new ItemId( 'z:Q1' ), new ItemId( 'a:Q1' ) ] );

		$this->assertEquals(
			[ new ItemId( 'a:Q1' ), new ItemId( 'z:Q1' ) ],
			$form->getGrammaticalFeatures()
		);
	}

	public function testSetGrammaticalFeatures_NonItemIdIsGiven_ThrowsException() {
		$form = NewForm::havingId( 'F1' )->build();

		$this->expectException( \InvalidArgumentException::class );
		$form->setGrammaticalFeatures( [ "Q1" ] );
	}

	public function testGivenFormWithInitiallyRequiredRepresentation_isNotEmpty() {
		$form = NewForm::havingRepresentation( 'en', 'one' )->build();
		$this->assertFalse( $form->isEmpty() );
	}

	public function testGivenFormWithInitiallyRequiredRepresentationRemoved_isEmpty() {
		$form = NewForm::havingRepresentation( 'en', 'one' )->build();
		$form->getRepresentations()->removeByLanguage( 'en' );
		$this->assertTrue( $form->isEmpty() );
	}

	public function provideNonEmptyForms() {
		return [
			'2 representations' => [
				NewForm::havingRepresentation( 'en', 'one' )
					->andRepresentation( 'fr', 'two' )
					->build()
			],
			'1 grammatical feature' => [
				NewForm::havingGrammaticalFeature( 'Q1' )
					->build()
			],
			'1 statement' => [
				NewForm::havingStatement( $this->newStatement() )
					->build()
			],
		];
	}

	/**
	 * @dataProvider provideNonEmptyForms
	 */
	public function testGivenFormWithOptionalElements_isNotEmpty( Form $form ) {
		$this->assertFalse( $form->isEmpty() );
	}

	public function provideEqualForms() {
		$minimal = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'minimal' );
		$nonEmpty = $minimal->andGrammaticalFeature( 'Q1' )
			->andStatement( $this->newStatement() );

		$minimalInstance = $minimal->build();

		return [
			'same instance' => [
				$minimalInstance,
				$minimalInstance
			],
			'minimal forms' => [
				$minimal->build(),
				$minimal->build()
			],
			'different IDs' => [
				$minimal->build(),
				NewForm::havingId( 'F2' )->andRepresentation( 'en', 'minimal' )->build()
			],
			'non-empty forms' => [
				$nonEmpty->build(),
				$nonEmpty->build()
			],
			'multiple grammatical features' => [
				$nonEmpty->andGrammaticalFeature( 'Q2' )->build(),
				$nonEmpty->andGrammaticalFeature( 'Q2' )->build()
			],
			'grammatical features in different order' => [
				$minimal->andGrammaticalFeature( 'Q1' )->andGrammaticalFeature( 'Q2' )->build(),
				$minimal->andGrammaticalFeature( 'Q2' )->andGrammaticalFeature( 'Q1' )->build()
			],
		];
	}

	/**
	 * @dataProvider provideEqualForms
	 */
	public function testGivenEqualForms_areEqual( Form $form1, Form $form2 ) {
		$this->assertTrue( $form1->equals( $form2 ) );
	}

	public function provideUnequalForms() {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'minimal' );

		return [
			'different representations' => [
				$form->build(),
				NewForm::havingId( 'F1' )->andRepresentation( 'en', 'different' )->build()
			],
			'+1 representation' => [
				$form->build(),
				$form->andRepresentation( 'fr', 'two' )->build()
			],
			'+1 grammatical feature' => [
				$form->build(),
				$form->andGrammaticalFeature( 'Q1' )->build()
			],
			'+1 statement' => [
				$form->build(),
				$form->andStatement( $this->newStatement() )->build()
			],
		];
	}

	/**
	 * @dataProvider provideUnequalForms
	 */
	public function testGivenUnequalForms_areNotEqual( Form $form1, Form $form2 ) {
		$this->assertFalse( $form1->equals( $form2 ) );
	}

	public function testCopyIsIndependent() {
		$original = NewForm::havingId( 'F1' )->build();
		$copy = $original->copy();

		// Edit all mutable fields on the original
		$original->getRepresentations()->setTextForLanguage( 'en', 'added' );
		$original->setGrammaticalFeatures( [ new ItemId( 'Q2' ) ] );
		$original->getStatements()->addStatement( $this->newStatement() );

		// Make sure the original changed
		$this->assertTrue( $original->getRepresentations()->hasTermForLanguage( 'en' ) );
		$this->assertNotEmpty( $original->getGrammaticalFeatures() );
		$this->assertFalse( $original->getStatements()->isEmpty() );

		// None of these changes should make it to the copy
		$this->assertFalse( $copy->getRepresentations()->hasTermForLanguage( 'en' ) );
		$this->assertSame( [], $copy->getGrammaticalFeatures() );
		$this->assertTrue( $copy->getStatements()->isEmpty() );
	}

	private function newStatement() {
		return new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ) );
	}

	/**
	 * @dataProvider clearableProvider
	 */
	public function testClear( Form $form ) {
		$clone = $form->copy();

		$form->clear();

		$this->assertTrue( $form->isEmpty(), 'form should be empty after clear' );
		$this->assertEquals( $clone->getId(), $form->getId(), 'ids must be equal' );
	}

	public function testSetIdCanSetFormId() {
		$id = new FormId( 'L1-F123' );
		$form = new Form( new NullFormId(), new TermList(), [] );
		$form->setId( $id );

		$this->assertSame( $id, $form->getId() );
	}

	public function testSetIdCanSetDummyFormId() {
		$id = new DummyFormId( 'L1-F123' );
		$form = new Form( new NullFormId(), new TermList(), [] );
		$form->setId( $id );

		$this->assertSame( $id, $form->getId() );
	}

	public function testSetIdCanSetFormIdReplacingDummyFormId() {
		$id = new FormId( 'L1-F123' );
		$form = new Form( new DummyFormId( 'L1-F123' ), new TermList(), [] );
		$form->setId( $id );

		$this->assertSame( $id, $form->getId() );
	}

	public function testGivenFormAlreadyHasRealId_setIdThrowsException() {
		$form = NewForm::havingId( new FormId( 'L1-F1' ) )->build();
		$this->expectException( LogicException::class );
		$form->setId( new FormId( 'L2-F2' ) );
	}

	/**
	 * @dataProvider nonFormIdProvider
	 */
	public function testGivenNotAFormId_setIdThrowsException( $id ) {
		$this->expectException( InvalidArgumentException::class );
		NewForm::any()->build()->setId( $id );
	}

	public function nonFormIdProvider() {
		yield [ 'L1-F1' ];
		yield [ null ];
		yield [ new ItemId( 'Q1' ) ];
		yield [ new LexemeId( 'L1' ) ];
	}

	public function clearableProvider() {
		return [
			'empty' => [ NewForm::havingId( 'F1' )->build() ],
			'with representation' => [
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'foo' )
					->build(),
			],
			'with grammatical feature' => [
				NewForm::havingId( 'F3' )
					->andGrammaticalFeature( 'Q123' )
					->build(),
			],
			'with statement' => [
				NewForm::havingId( 'F4' )
					->andStatement( new PropertyNoValueSnak( 42 ) )
					->build(),
			],
		];
	}

}

<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use InvalidArgumentException;
use LogicException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @covers \Wikibase\Lexeme\DataModel\Form
 *
 * @license GPL-2.0+
 */
class FormTest extends PHPUnit_Framework_TestCase {

	public function testCreateFormWithoutRepresentations_ThrowsAnException() {
		$this->setExpectedException( InvalidArgumentException::class );
		new Form( new FormId( 'L1-F1' ), new TermList(), [] );
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
		$this->setExpectedException( InvalidArgumentException::class );
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

		$this->setExpectedException( \InvalidArgumentException::class );
		$form->setGrammaticalFeatures( [ "Q1" ] );
	}

	public function testFormIdCanNotBeChanged() {
		$form = NewForm::havingId( 'F1' )->build();
		$this->setExpectedException( LogicException::class );
		$form->setId( new FormId( 'L1-F2' ) );
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
		$this->assertEmpty( $copy->getGrammaticalFeatures() );
		$this->assertTrue( $copy->getStatements()->isEmpty() );
	}

	private function newStatement() {
		return new Statement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) );
	}

}

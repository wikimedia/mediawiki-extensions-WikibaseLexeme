<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\DummyFormId;
use Wikibase\Lexeme\Domain\DummyObjects\NullFormId;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\FormSet;

/**
 * @covers \Wikibase\Lexeme\Domain\Model\FormSet
 *
 * @license GPL-2.0-or-later
 */
class FormSetTest extends MediaWikiUnitTestCase {

	public function testCanNotCreateWithSomethingThatIsNotAForm() {
		$this->expectException( \Exception::class );
		new FormSet( [ 1 ] );
	}

	public function testToArray() {
		$form = NewForm::any()->build();
		$formSet = new FormSet( [ $form ] );

		$this->assertEquals( [ $form ], $formSet->toArray() );
	}

	public function testToArrayUnordered() {
		$form = NewForm::any()->build();
		$formSet = new FormSet( [ $form ] );

		$this->assertEquals( [ $form ], $formSet->toArrayUnordered() );
	}

	public function testCanNotCreateWithTwoFormsHavingTheSameId() {
		$this->expectException( \Exception::class );

		new FormSet(
			[
				NewForm::havingId( 'F1' )->build(),
				NewForm::havingId( 'F1' )->build(),
			]
		);
	}

	public function testCount() {
		$this->assertSame( 0, ( new FormSet() )->count() );
		$this->assertSame( 1, ( new FormSet( [ NewForm::any()->build() ] ) )->count() );
	}

	public function testIsEmpty() {
		$this->assertTrue( ( new FormSet() )->isEmpty() );
		$this->assertFalse( ( new FormSet( [ NewForm::any()->build() ] ) )->isEmpty() );
	}

	public function testMaxFormIdNumber_EmptySet_ReturnsZero() {
		$this->assertSame( 0, ( new FormSet() )->maxFormIdNumber() );
	}

	public function testMaxFormIdNumber_SetWithOneForm_ReturnsThatFormIdNumber() {
		$formSet = new FormSet( [ NewForm::havingId( 'F1' )->build() ] );

		$this->assertSame( 1, $formSet->maxFormIdNumber() );
	}

	public function testMaxFormIdNumber_SetWithManyForms_ReturnsMaximumFormIdNumber() {
		$formSet = new FormSet(
			[
				NewForm::havingId( 'F1' )->build(),
				NewForm::havingId( 'F3' )->build(),
				NewForm::havingId( 'F2' )->build(),
			]
		);

		$this->assertEquals( 3, $formSet->maxFormIdNumber() );
	}

	public function testAddForm_EmptySet_FormIsAdded() {
		$formSet = new FormSet();
		$form = NewForm::havingId( 'F1' )->build();

		$formSet->add( $form );

		$this->assertEquals( [ $form ], $formSet->toArray() );
	}

	public function testAddForm_AddFormWithIdThatAlreadyPresentInTheSet_ThrowsAnException() {
		$formSet = new FormSet( [ NewForm::havingId( 'F1' )->build() ] );

		$this->expectException( \Exception::class );
		$formSet->add( NewForm::havingId( 'F1' )->build() );
	}

	public function testRemove_CanRemoveAForm() {
		$formSet = new FormSet( [ NewForm::havingId( 'F1' )->build() ] );

		$formSet->remove( new FormId( 'L1-F1' ) );

		$this->assertSame( [], $formSet->toArray() );
	}

	public function testPut_updatedFormReference() {
		$formSet = new FormSet( [ NewForm::havingId( 'F1' )->andLexeme( 'L81' )->build() ] );

		$newForm = NewForm::havingId( 'F1' )->andLexeme( 'L81' )->build();
		$formSet->put( $newForm );

		$this->assertSame( [ $newForm ], $formSet->toArray() );
	}

	public function testIndependentlyOnFormAdditionOrder_TwoSetsAreEqualIfTheyHaveTheSameForms() {
		$form1 = NewForm::havingId( 'F1' )->build();
		$form2 = NewForm::havingId( 'F2' )->build();

		$formSet1 = new FormSet( [ $form1, $form2 ] );
		$formSet2 = new FormSet( [ $form2, $form1 ] );

		$this->assertEquals( $formSet1, $formSet2 );
	}

	/**
	 * Forms can only be accessed through FormSet::toArray(), which enforces right order,
	 * one-by-one through id, where order is irrelevant,
	 * or through FormSet::toArrayUnordered(), where the caller has explicitly declared
	 * that the order is not relevant.
	 */
	public function testToArray_ReturnedFormsAreSortedByTheirId() {
		$form1 = NewForm::havingId( 'F2' )->build();
		$form2 = NewForm::havingId( 'F12' )->build();

		$formSet2 = new FormSet( [ $form2, $form1 ] );

		$this->assertEquals( [ $form1, $form2 ], $formSet2->toArray() );
	}

	public function testCopyClonesForms() {
		$form1 = NewForm::havingId( 'F1' )->build();
		$form2 = NewForm::havingId( 'F2' )->build();

		$formSet = new FormSet( [ $form2, $form1 ] );

		$formSetCopy = $formSet->copy();

		$this->assertNotSame(
			$formSet,
			$formSetCopy
		);
		$this->assertNotSame(
			$formSet->getById( $form1->getId() ),
			$formSetCopy->getById( $form1->getId() )
		);
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( FormSet $set1, $set2, $isEqual ) {
		$this->assertSame( $isEqual, $set1->equals( $set2 ) );
	}

	public function equalsProvider() {
		yield 'empty sets' => [
			new FormSet(),
			new FormSet(),
			true
		];

		yield 'not a FormSet - not equal' => [
			new FormSet(),
			[],
			false
		];

		$form = NewForm::havingId( new FormId( 'L1-F1' ) )
			->andRepresentation( 'en', 'potato' )
			->build();
		yield 'same Form' => [
			new FormSet( [ $form ] ),
			new FormSet( [ $form->copy() ] ),
			true
		];

		$form2 = NewForm::havingId( new FormId( 'L12-F2' ) )
			->andRepresentation( 'de', 'Kartoffel' )
			->build();
		yield 'different order of Forms' => [
			new FormSet( [ $form, $form2 ] ),
			new FormSet( [ $form2, $form ] ),
			true
		];

		$blankForm = new BlankForm();
		$blankForm->setId( $form->getId() );
		$blankForm->setRepresentations( $form->getRepresentations() );
		yield 'Form and equivalent BlankForm' => [
			new FormSet( [ $form ] ),
			new FormSet( [ $blankForm ] ),
			true
		];

		$form3 = NewForm::havingId( new FormId( 'L12-F3' ) )
			->andRepresentation( 'ru', 'карто́фель' )
			->build();
		yield 'one replaced Form but same length' => [
			new FormSet( [ $form, $form2 ] ),
			new FormSet( [ $form, $form3 ] ),
			false
		];
	}

	/**
	 * @dataProvider provideFormSetAndContainedFormId
	 */
	public function testHasFormWithId_detectsContainedForms( FormSet $formSet, FormId $formId ) {
		$this->assertTrue( $formSet->hasFormWithId( $formId ) );
	}

	public function provideFormSetAndContainedFormId() {
		yield 'FormId already contained in set' => [
			new FormSet( [ NewForm::havingLexeme( 'L42' )->andId( 'F1' )->build() ] ),
			new FormId( 'L42-F1' )
		];
		yield 'DummyFormId already contained in set' => [
			new FormSet( [ NewForm::havingLexeme( 'L42' )->andId( 'F1' )->build() ] ),
			new DummyFormId( 'L42-F1' )
		];
	}

	/**
	 * @dataProvider provideFormSetAndUnaccountedFormId
	 */
	public function testHasFormWithId_detectsUnaccountedForms( FormSet $formSet, FormId $formId ) {
		$this->assertFalse( $formSet->hasFormWithId( $formId ) );
	}

	public function provideFormSetAndUnaccountedFormId() {
		yield 'form not added to this set (yet)' => [
			new FormSet( [ NewForm::havingLexeme( 'L42' )->andId( 'F1' )->build() ] ),
			new FormId( 'L42-F17' )
		];
		yield 'unrelated lexeme' => [
			new FormSet( [ NewForm::havingLexeme( 'L42' )->andId( 'F1' )->build() ] ),
			new FormId( 'L4711-F1' )
		];
		yield 'form with a NullFormId' => [
			new FormSet( [ NewForm::havingLexeme( 'L42' )->andId( 'F1' )->build() ] ),
			new NullFormId()
		];
	}

}

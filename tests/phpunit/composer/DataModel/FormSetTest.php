<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\DummyObjects\BlankForm;

/**
 * @covers \Wikibase\Lexeme\DataModel\FormSet
 *
 * @license GPL-2.0-or-later
 */
class FormSetTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testCanNotCreateWithSomethingThatIsNotAForm() {
		$this->setExpectedException( \Exception::class );
		new FormSet( [ 1 ] );
	}

	public function testToArray() {
		$form = NewForm::any()->build();
		$formSet = new FormSet( [ $form ] );

		$this->assertEquals( [ $form ], $formSet->toArray() );
	}

	public function testCanNotCreateWithTwoFormsHavingTheSameId() {
		$this->setExpectedException( \Exception::class );

		new FormSet(
			[
				NewForm::havingId( 'F1' )->build(),
				NewForm::havingId( 'F1' )->build(),
			]
		);
	}

	public function testCount() {
		$this->assertEquals( 0, ( new FormSet() )->count() );
		$this->assertEquals( 1, ( new FormSet( [ NewForm::any()->build() ] ) )->count() );
	}

	public function testIsEmpty() {
		$this->assertTrue( ( new FormSet() )->isEmpty() );
		$this->assertFalse( ( new FormSet( [ NewForm::any()->build() ] ) )->isEmpty() );
	}

	public function testMaxFormIdNumber_EmptySet_ReturnsZero() {
		$this->assertEquals( 0, ( new FormSet() )->maxFormIdNumber() );
	}

	public function testMaxFormIdNumber_SetWithOneForm_ReturnsThatFormIdNumber() {
		$formSet = new FormSet( [ NewForm::havingId( 'F1' )->build() ] );

		$this->assertEquals( 1, $formSet->maxFormIdNumber() );
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

		$this->setExpectedException( \Exception::class );
		$formSet->add( NewForm::havingId( 'F1' )->build() );
	}

	public function testRemove_CanRemoveAForm() {
		$formSet = new FormSet( [ NewForm::havingId( 'F1' )->build() ] );

		$formSet->remove( new FormId( 'L1-F1' ) );

		$this->assertEmpty( $formSet->toArray() );
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
	 * or one-by-one through id, where order is irrelevant.
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

}

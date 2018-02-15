<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\FormSet;

/**
 * @covers \Wikibase\Lexeme\DataModel\FormSet
 *
 * @license GPL-2.0+
 */
class FormSetTest extends TestCase {

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

	public function testIndependentlyOnFormAdditionOrder_TwoSetsAreEqualIfTheyHaveTheSameForms() {
		$form1 = NewForm::havingId( 'F1' )->build();
		$form2 = NewForm::havingId( 'F2' )->build();

		$formSet1 = new FormSet( [ $form1, $form2 ] );
		$formSet2 = new FormSet( [ $form2, $form1 ] );

		$this->assertEquals( $formSet1, $formSet2 );
	}

	public function testToArray_ReturnedFormsAreSortedByTheirId() {
		$form1 = NewForm::havingId( 'F1' )->build();
		$form2 = NewForm::havingId( 'F2' )->build();

		$formSet2 = new FormSet( [ $form2, $form1 ] );

		$this->assertEquals( [ $form1, $form2 ], $formSet2->toArray() );
	}

}

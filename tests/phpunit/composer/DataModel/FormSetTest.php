<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\Lexeme\DataModel\FormSet;

class FormSetTest extends \PHPUnit_Framework_TestCase {

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
		$this->assertEquals( 0, ( new FormSet( [] ) )->count() );
		$this->assertEquals( 1, ( new FormSet( [ NewForm::any()->build() ] ) )->count() );
	}

	public function testMaxFormIdNumber_EmptySet_ReturnsZero() {
		$this->assertEquals( 0, ( new FormSet( [] ) )->maxFormIdNumber() );
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
		$formSet = new FormSet( [] );
		$form = NewForm::havingId( 'F1' )->build();

		$formSet->add( $form );

		$this->assertEquals( [ $form ], $formSet->toArray() );
	}

	public function testAddForm_AddFormWithIdThatAlreadyPresentInTheSet_ThrowsAnException() {
		$formSet = new FormSet( [ NewForm::havingId( 'F1' )->build() ] );

		$this->setExpectedException( \Exception::class );
		$formSet->add( NewForm::havingId( 'F1' )->build() );
	}

}

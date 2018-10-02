<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use ValueValidators\Result;
use Wikibase\Lexeme\ChangeOp\AddFormToLexemeChangeOp;
use Wikibase\Lexeme\DummyObjects\BlankForm;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\AddFormToLexemeChangeOp
 *
 * @license GPL-2.0-or-later
 */
class AddFormToLexemeChangeOpTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testAction_isEdit() {
		$changeOp = new AddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = new AddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonBlankForm_yieldsAssertionProblem() {
		$changeOp = new AddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->validate( NewForm::any()->build() );
	}

	public function testValidateBlankForm_yieldsSuccess() {
		$changeOp = new AddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$result = $changeOp->validate( new BlankForm() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testApplyNonForm_yieldsAssertionProblem() {
		$changeOp = new AddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testApplyNonBlankForm_yieldsAssertionProblem() {
		$changeOp = new AddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->apply( NewForm::any()->build() );
	}

	public function testApply_connectsLexemeToForm() {
		$lexeme = NewLexeme::create()->build();
		$blankForm = $this->getMock( BlankForm::class );
		$blankForm->expects( $this->once() )
			->method( 'setLexeme' )
			->with( $lexeme );

		$changeOp = new AddFormToLexemeChangeOp( $lexeme );
		$changeOp->apply( $blankForm );
	}

}

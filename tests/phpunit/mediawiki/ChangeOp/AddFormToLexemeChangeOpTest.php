<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use ValueValidators\Result;
use Wikibase\Lexeme\ChangeOp\AddFormToLexemeChangeOp;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\Lexeme\DummyObjects\BlankForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\AddFormToLexemeChangeOp
 *
 * @license GPL-2.0-or-later
 */
class AddFormToLexemeChangeOpTest extends TestCase {

	use PHPUnit4And6Compat;

	/** @var ChangeOp|MockObject */
	private $changeOpFormEdit;

	public function setUp() {
		parent::setUp();
		$this->changeOpFormEdit = $this->createMock( ChangeOpFormEdit::class );
	}

	public function testAction_isEdit() {
		$changeOp = $this->newAddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $form
	 */
	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = $this->newAddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateBlankForm_yieldsSuccess() {
		$changeOp = $this->newAddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$result = $changeOp->validate( new BlankForm() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $form
	 */
	public function testApplyNonForm_yieldsAssertionProblem() {
		$changeOp = $this->newAddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_connectsLexemeToForm() {
		$lexeme = $this->createMock( Lexeme::class );
		$blankForm = $this->getMock( BlankForm::class );

		$lexeme->expects( $this->once() )
			->method( 'addOrUpdateForm' )
			->with( $blankForm );
		$this->changeOpFormEdit->expects( $this->once() )
			->method( 'apply' )
			->with( $blankForm );

		$changeOp = $this->newAddFormToLexemeChangeOp( $lexeme );
		$changeOp->apply( $blankForm );
	}

	/**
	 * @param $lexeme
	 *
	 * @return AddFormToLexemeChangeOp
	 */
	private function newAddFormToLexemeChangeOp( $lexeme ) {
		return new AddFormToLexemeChangeOp( $lexeme, $this->changeOpFormEdit );
	}

}

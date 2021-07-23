<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\Lexeme\DataAccess\ChangeOp\AddFormToLexemeChangeOp;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\AddFormToLexemeChangeOp
 *
 * @license GPL-2.0-or-later
 */
class AddFormToLexemeChangeOpTest extends TestCase {

	/** @var ChangeOp|MockObject */
	private $changeOpFormEdit;

	protected function setUp(): void {
		parent::setUp();
		$this->changeOpFormEdit = $this->createMock( ChangeOpFormEdit::class );
	}

	public function testAction_isEdit() {
		$changeOp = $this->newAddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	public function testValidateNonForm_yieldsAssertionProblem() {
		$changeOp = $this->newAddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $form' );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateBlankForm_yieldsSuccess() {
		$changeOp = $this->newAddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$result = $changeOp->validate( new BlankForm() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testApplyNonForm_yieldsAssertionProblem() {
		$changeOp = $this->newAddFormToLexemeChangeOp( NewLexeme::create()->build() );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $form' );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_connectsLexemeToForm() {
		$lexeme = $this->createMock( Lexeme::class );
		$blankForm = $this->createMock( BlankForm::class );

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

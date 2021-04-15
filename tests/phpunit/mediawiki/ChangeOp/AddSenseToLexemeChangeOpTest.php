<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\Lexeme\DataAccess\ChangeOp\AddSenseToLexemeChangeOp;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\AddSenseToLexemeChangeOp
 *
 * @license GPL-2.0-or-later
 */
class AddSenseToLexemeChangeOpTest extends TestCase {

	public function testAction_isEdit() {
		$changeOp = new AddSenseToLexemeChangeOp(
			NewLexeme::create()->build(),
			$this->createStub( ChangeOp::class )
		);
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new AddSenseToLexemeChangeOp(
			NewLexeme::create()->build(),
			$this->createStub( ChangeOp::class )
		);
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateNonBlankSense_yieldsAssertionProblem() {
		$changeOp = new AddSenseToLexemeChangeOp(
			NewLexeme::create()->build(),
			$this->createStub( ChangeOp::class )
		);
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->validate( NewSense::havingId( 'S1' )->build() );
	}

	public function testValidateBlankSense_yieldsSuccess() {
		$changeOp = new AddSenseToLexemeChangeOp(
			NewLexeme::create()->build(),
			$this->createStub( ChangeOp::class )
		);
		$result = $changeOp->validate( new BlankSense() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testApplyNonSense_yieldsAssertionProblem() {
		$changeOp = new AddSenseToLexemeChangeOp(
			NewLexeme::create()->build(),
			$this->createStub( ChangeOp::class )
		);
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApplyNonBlankSense_yieldsAssertionProblem() {
		$changeOp = new AddSenseToLexemeChangeOp(
			NewLexeme::create()->build(),
			$this->createStub( ChangeOp::class )
		);
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->apply( NewSense::havingId( 'S1' )->build() );
	}

	public function testApply_connectsLexemeToSense() {
		$lexeme = $this->createMock( Lexeme::class );
		$blankSense = $this->createStub( BlankSense::class );

		$lexeme->expects( $this->once() )
			->method( 'addOrUpdateSense' )
			->with( $blankSense );

		$changeOp = new AddSenseToLexemeChangeOp( $lexeme, $this->createStub( ChangeOp::class ) );
		$changeOp->apply( $blankSense );
	}

	public function testApply_appliesEditChangeOp() {
		$blankSense = new BlankSense();
		$changeOpSenseEdit = $this->createMock( ChangeOp::class );
		$changeOp = new AddSenseToLexemeChangeOp(
			NewLexeme::havingId( 'L123' )->build(),
			$changeOpSenseEdit
		);

		$changeOpSenseEdit->expects( $this->once() )
			->method( 'apply' )
			->with( $blankSense );

		$changeOp->apply( $blankSense );
	}

	public function testValidate_validatesEditChangeOp() {
		$blankSense = $this->createStub( BlankSense::class );
		$changeOpSenseEdit = $this->createMock( ChangeOp::class );
		$changeOp = new AddSenseToLexemeChangeOp(
			NewLexeme::create()->build(),
			$changeOpSenseEdit
		);

		$changeOpSenseEdit->expects( $this->once() )
			->method( 'validate' )
			->with( $blankSense );

		$changeOp->validate( $blankSense );
	}

}

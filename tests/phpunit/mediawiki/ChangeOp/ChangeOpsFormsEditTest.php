<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpsFormsEdit;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Summary;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpsFormsEdit
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpsFormsEditTest extends TestCase {
	use PHPUnit4And6Compat;

	public function testValidateForm_yieldsError() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [] );

		$form = NewForm::any()->build();

		$this->setExpectedException( ParameterTypeException::class );

		$changeOpRemoveForm->validate( $form );
	}

	public function testValidateLexemeWithEmptyChangeOps_yieldsSuccess() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [] );

		$lexeme = NewLexeme::create()->build();

		$this->assertTrue( $changeOpRemoveForm->validate( $lexeme )->isValid() );
	}

	public function testValidateLexemeWithUnknownFormOp_yieldsError() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [
			'L1-F1' => $this->getMockBuilder( ChangeOpFormEdit::class )
				->disableOriginalConstructor()
				->getMock()
		] );

		$lexeme = NewLexeme::create()->build();

		$this->assertFalse( $changeOpRemoveForm->validate( $lexeme )->isValid() );
	}

	public function testValidateLexemeWithFormOp_yieldsSuccess() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [
			'L1-F1' => $this->getMockBuilder( ChangeOpFormEdit::class )
				->disableOriginalConstructor()
				->getMock()
		] );

		$form = NewForm::havingLexeme( 'L1' )->andId( 'F1' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->assertTrue( $changeOpRemoveForm->validate( $lexeme )->isValid() );
	}

	public function testApplyForm_yieldsError() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [] );

		$form = NewForm::any()->build();

		$this->setExpectedException( ParameterTypeException::class );

		$changeOpRemoveForm->apply( $form );
	}

	public function testApplyLexemeWithMatchingFormOp_propagatesApplyWithFormAndSummary() {
		$form = NewForm::havingId( new FormId( 'L1-F1' ) )->build();
		$lexeme = NewLexeme::create()->withForm( $form )->build();

		$summary = new Summary();

		$op = $this->getMockBuilder( ChangeOpFormEdit::class )
			->disableOriginalConstructor()
			->getMock();
		$op->expects( $this->once() )
			->method( 'apply' )
			->with( $form, $summary );

		$changeOpRemoveForm = new ChangeOpsFormsEdit( [
			'L1-F1' => $op
		] );

		$changeOpRemoveForm->apply( $lexeme, $summary );
	}

}

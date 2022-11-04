<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpFormEdit;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpsFormsEdit;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Summary;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpsFormsEdit
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpsFormsEditTest extends TestCase {

	public function testValidateForm_yieldsError() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [] );

		$form = NewForm::any()->build();

		$this->expectException( ParameterTypeException::class );

		$changeOpRemoveForm->validate( $form );
	}

	public function testValidateLexemeWithEmptyChangeOps_yieldsSuccess() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [] );

		$lexeme = NewLexeme::create()->build();

		$this->assertTrue( $changeOpRemoveForm->validate( $lexeme )->isValid() );
	}

	public function testValidateLexemeWithUnknownFormOp_yieldsError() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [
			'L1-F1' => $this->createMock( ChangeOpFormEdit::class )
		] );

		$lexeme = NewLexeme::create()->build();

		$this->assertFalse( $changeOpRemoveForm->validate( $lexeme )->isValid() );
	}

	public function testValidateLexemeWithFormOp_yieldsSuccess() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [
			'L1-F1' => $this->createMock( ChangeOpFormEdit::class )
		] );

		$form = NewForm::havingLexeme( 'L1' )->andId( 'F1' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withForm( $form )->build();

		$this->assertTrue( $changeOpRemoveForm->validate( $lexeme )->isValid() );
	}

	public function testApplyForm_yieldsError() {
		$changeOpRemoveForm = new ChangeOpsFormsEdit( [] );

		$form = NewForm::any()->build();

		$this->expectException( ParameterTypeException::class );

		$changeOpRemoveForm->apply( $form );
	}

	public function testApplyLexemeWithMatchingFormOp_propagatesApplyWithFormAndSummary() {
		$form = NewForm::havingId( new FormId( 'L1-F1' ) )->build();
		$lexeme = NewLexeme::create()->withForm( $form )->build();

		$summary = new Summary();

		$op = $this->createMock( ChangeOpFormEdit::class );
		$op->expects( $this->once() )
			->method( 'apply' )
			->with( $form, $summary );

		$changeOpRemoveForm = new ChangeOpsFormsEdit( [
			'L1-F1' => $op
		] );

		$changeOpRemoveForm->apply( $lexeme, $summary );
	}

}

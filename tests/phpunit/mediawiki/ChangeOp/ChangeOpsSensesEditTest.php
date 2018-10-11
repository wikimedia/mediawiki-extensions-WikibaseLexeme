<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Lexeme\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\ChangeOp\ChangeOpsSensesEdit;
use Wikibase\Lexeme\Domain\DataModel\SenseId;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Summary;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\ChangeOpsSensesEdit
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpsSensesEditTest extends TestCase {
	use PHPUnit4And6Compat;

	public function testValidateSense_yieldsError() {
		$changeOpRemoveSense = new ChangeOpsSensesEdit( [] );

		$sense = ( new NewSense() )->build();

		$this->setExpectedException( ParameterTypeException::class );

		$changeOpRemoveSense->validate( $sense );
	}

	public function testValidateLexemeWithEmptyChangeOps_yieldsSuccess() {
		$changeOpRemoveSense = new ChangeOpsSensesEdit( [] );

		$lexeme = NewLexeme::create()->build();

		$this->assertTrue( $changeOpRemoveSense->validate( $lexeme )->isValid() );
	}

	public function testValidateLexemeWithUnknownSenseOp_yieldsError() {
		$changeOpRemoveSense = new ChangeOpsSensesEdit( [
			'L1-S1' => $this->getMockBuilder( ChangeOpSenseEdit::class )
				->disableOriginalConstructor()
				->getMock()
		] );

		$lexeme = NewLexeme::create()->build();

		$this->assertFalse( $changeOpRemoveSense->validate( $lexeme )->isValid() );
	}

	public function testValidateLexemeWithSenseOp_yieldsSuccess() {
		$changeOpRemoveSense = new ChangeOpsSensesEdit( [
			'L1-S1' => $this->getMockBuilder( ChangeOpSenseEdit::class )
				->disableOriginalConstructor()
				->getMock()
		] );

		$sense = NewSense::havingId( 'S1' )->andLexeme( 'L1' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )->withSense( $sense )->build();

		$this->assertTrue( $changeOpRemoveSense->validate( $lexeme )->isValid() );
	}

	public function testApplySense_yieldsError() {
		$changeOpRemoveSense = new ChangeOpsSensesEdit( [] );

		$sense = ( new NewSense() )->build();

		$this->setExpectedException( ParameterTypeException::class );

		$changeOpRemoveSense->apply( $sense );
	}

	public function testApplyLexemeWithMatchingSenseOp_propagatesApplyWithSenseAndSummary() {
		$sense = NewSense::havingId( new SenseId( 'L1-S1' ) )->build();
		$lexeme = NewLexeme::create()->withSense( $sense )->build();

		$summary = new Summary();

		$op = $this->getMockBuilder( ChangeOpSenseEdit::class )
			->disableOriginalConstructor()
			->getMock();
		$op->expects( $this->once() )
			->method( 'apply' )
			->with( $sense, $summary );

		$changeOpRemoveSense = new ChangeOpsSensesEdit( [
			'L1-S1' => $op
		] );

		$changeOpRemoveSense->apply( $lexeme, $summary );
	}

}

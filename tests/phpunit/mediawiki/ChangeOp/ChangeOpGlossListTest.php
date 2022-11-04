<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGlossList;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSenseGloss;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGlossList
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpGlossListTest extends TestCase {

	public function testAction_isEdit() {
		$changeOp = new ChangeOpGlossList( [] );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpGlossList( [] );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnySense_yieldsSuccess() {
		$changeOp = new ChangeOpGlossList( [] );
		$result = $changeOp->validate( NewSense::havingId( 'S1' )->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testApplyNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpGlossList( [] );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_propagatesApplyToChangeOps() {
		$sense = NewSense::havingId( 'S1' )->build();

		$op1 = $this->createMock( ChangeOp::class );
		$op1->expects( $this->once() )
			->method( 'apply' )
			->with( $sense, new Summary() );
		$op2 = $this->createMock( ChangeOp::class );
		$op2->expects( $this->once() )
			->method( 'apply' )
			->with( $sense, new Summary() );

		$changeOp = new ChangeOpGlossList( [ $op1, $op2 ] );
		$changeOp->apply( $sense );
	}

	public function testApply_doesNothingOnEmptyChangeOps() {
		$sense = NewSense::havingId( 'S1' )->build();
		$senseClone = clone $sense;

		$changeOp = new ChangeOpGlossList( [] );
		$changeOp->apply( $sense );

		$this->assertTrue( $sense->equals( $senseClone ) );
	}

	public function testApplySameAction_atomicActionInSummary() {
		$sense = NewSense::havingId( 'S1' )->build();
		$summary = new Summary();

		$op1 = $this->createMock( ChangeOp::class );
		$op1->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function ( Sense $a, Summary $b ) use ( $sense ) {
				$this->assertSame( $sense, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'f' ] );
				$b->setAutoSummaryArgs( [ 'ff' ] );
			} );
		$op2 = $this->createMock( ChangeOp::class );
		$op2->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function ( Sense $a, Summary $b ) use ( $sense ) {
				$this->assertSame( $sense, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'g' ] );
				$b->setAutoSummaryArgs( [ 'gg' ] );
			} );

		$changeOp = new ChangeOpGlossList( [ $op1, $op2 ] );
		$changeOp->apply( $sense, $summary );

		$this->assertSame( 'specific-action', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'f', 'g' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'ff', 'gg' ], $summary->getAutoSummaryArgs() );
	}

	public function testApplyDifferentActions_aggregateActionInSummary() {
		$sense = NewSense::havingId( 'S1' )->build();
		$summary = new Summary();

		$op1 = $this->createMock( ChangeOp::class );
		$op1->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function ( Sense $a, Summary $b ) use ( $sense ) {
				$this->assertSame( $sense, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'f' ] );
				$b->setAutoSummaryArgs( [ 'ff' ] );
			} );
		$op2 = $this->createMock( ChangeOp::class );
		$op2->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function ( Sense $a, Summary $b ) use ( $sense ) {
				$this->assertSame( $sense, $a );

				$b->setAction( 'other-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'g' ] );
				$b->setAutoSummaryArgs( [ 'gg' ] );
			} );

		$changeOp = new ChangeOpGlossList( [ $op1, $op2 ] );
		$changeOp->apply( $sense, $summary );

		$this->assertSame( 'update-sense-glosses', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'f', 'g' ], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

	public function testGetChangeOps_yieldsConstructorParameters() {
		$op1 = new ChangeOpRemoveSenseGloss( 'en' );
		$op2 = new ChangeOpRemoveSenseGloss( 'de' );
		$changeOp = new ChangeOpGlossList( [ $op1, $op2 ] );
		$this->assertSame( [ $op1, $op2 ], $changeOp->getChangeOps() );
	}

}

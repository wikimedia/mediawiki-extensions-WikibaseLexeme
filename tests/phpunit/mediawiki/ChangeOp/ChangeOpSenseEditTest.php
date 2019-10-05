<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use ValueValidators\Result;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSenseGloss;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseEdit
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseEditTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testAction_isEdit() {
		$changeOp = new ChangeOpSenseEdit( [] );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpSenseEdit( [] );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnySense_yieldsSuccess() {
		$changeOp = new ChangeOpSenseEdit( [] );
		$result = $changeOp->validate( NewSense::havingId( 'S1' )->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	/**
	 * @expectedException \Wikimedia\Assert\ParameterTypeException
	 * @expectedExceptionMessage Bad value for parameter $entity
	 */
	public function testApplyNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpSenseEdit( [] );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_propagatesApplyToChangeOps() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();

		$op1 = $this->createMock( ChangeOp::class );
		$op1->expects( $this->once() )
			->method( 'apply' )
			->with( $sense, new Summary() );
		$op2 = $this->createMock( ChangeOp::class );
		$op2->expects( $this->once() )
			->method( 'apply' )
			->with( $sense, new Summary() );

		$changeOp = new ChangeOpSenseEdit( [ $op1, $op2 ] );
		$changeOp->apply( $sense );
	}

	public function testApply_doesNothingOnEmptyChangeOps() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$senseClone = clone $sense;

		$changeOp = new ChangeOpSenseEdit( [] );
		$changeOp->apply( $sense );

		$this->assertTrue( $sense->equals( $senseClone ) );
	}

	public function testApplySameAction_atomicActionInSummary() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$summary = new Summary();

		$op1 = $this->getMockBuilder( ChangeOp::class )
			->disableArgumentCloning()
			->getMock();
		$op1->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function( Sense $a, Summary $b ) use ( $sense ) {
				$this->assertSame( $sense, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'f' ] );
				$b->setAutoSummaryArgs( [ 'ff' ] );
			} );
		$op2 = $this->getMockBuilder( ChangeOp::class )
			->disableArgumentCloning()
			->getMock();
		$op2->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function( Sense $a, Summary $b ) use ( $sense ) {
				$this->assertSame( $sense, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'g' ] );
				$b->setAutoSummaryArgs( [ 'gg' ] );
			} );

		$changeOp = new ChangeOpSenseEdit( [ $op1, $op2 ] );
		$changeOp->apply( $sense, $summary );

		$this->assertSame( 'specific-action', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'f', 'g' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'ff', 'gg' ], $summary->getAutoSummaryArgs() );
	}

	public function testApplyDifferentActions_aggregateActionInSummary() {
		$sense = NewSense::havingId( 'S1' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$summary = new Summary();

		$op1 = $this->getMockBuilder( ChangeOp::class )
			->disableArgumentCloning()
			->getMock();
		$op1->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function( Sense $a, Summary $b ) use ( $sense ) {
				$this->assertSame( $sense, $a );

				$b->setAction( 'specific-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'f' ] );
				$b->setAutoSummaryArgs( [ 'ff' ] );
			} );
		$op2 = $this->getMockBuilder( ChangeOp::class )
			->disableArgumentCloning()
			->getMock();
		$op2->expects( $this->once() )
			->method( 'apply' )
			->willReturnCallback( function( Sense $a, Summary $b ) use ( $sense ) {
				$this->assertSame( $sense, $a );

				$b->setAction( 'other-action' );
				$b->setLanguage( 'en' );
				$b->setAutoCommentArgs( [ 'g' ] );
				$b->setAutoSummaryArgs( [ 'gg' ] );
			} );

		$changeOp = new ChangeOpSenseEdit( [ $op1, $op2 ] );
		$changeOp->apply( $sense, $summary );

		$this->assertSame( 'update-sense-elements', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'f', 'g' ], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

	/**
	 * @expectedException \Wikibase\Repo\ChangeOp\ChangeOpApplyException
	 * @expectedExceptionMessage apierror-wikibaselexeme-sense-must-have-at-least-one-gloss
	 */
	public function testApplyRemovingOnlyGlosses_throwsException() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'en', 'furry animal' )
			->build();

		$changeOp = new ChangeOpSenseEdit( [ new ChangeOpRemoveSenseGloss( 'en' ) ] );
		$changeOp->apply( $sense );
	}

	public function testGetChangeOps_yieldsConstructorParameters() {
		$op1 = new ChangeOpRemoveSenseGloss( 'en' );
		$op2 = new ChangeOpRemoveSenseGloss( 'de' );
		$changeOp = new ChangeOpSenseEdit( [ $op1, $op2 ] );
		$this->assertSame( [ $op1, $op2 ], $changeOp->getChangeOps() );
	}

}

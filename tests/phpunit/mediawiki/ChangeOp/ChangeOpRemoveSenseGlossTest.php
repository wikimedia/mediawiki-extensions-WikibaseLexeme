<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSenseGloss;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Summary;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSenseGloss
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveSenseGlossTest extends TestCase {

	public function testAction_isEdit() {
		$changeOp = new ChangeOpRemoveSenseGloss( 'en' );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpRemoveSenseGloss( 'en' );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnySense_yieldsSuccess() {
		$changeOp = new ChangeOpRemoveSenseGloss( 'en' );
		$result = $changeOp->validate( NewSense::havingId( 'S1' )->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testApplyNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpRemoveSenseGloss( 'en' );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_removesGlossInPreexistingLanguage() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpRemoveSenseGloss( 'en' );
		$changeOp->apply( $sense, $summary );

		$this->assertCount( 0, $sense->getGlosses() );
		$this->assertSame( 'remove-sense-glosses', $summary->getMessageKey() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-S3' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'en' => 'furry animal' ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_noOpForNonPreexistingLanguage() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'en', 'furry animal' )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpRemoveSenseGloss( 'de' );
		$changeOp->apply( $sense, $summary );

		$this->assertCount( 1, $sense->getGlosses() );
		$this->assertNull( $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

}

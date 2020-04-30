<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Summary;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpGloss
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpGlossTest extends TestCase {

	public function testAction_isEdit() {
		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$this->assertSame( [ 'edit' ], $changeOp->getActions() );
	}

	public function testValidateNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->validate( NewLexeme::create()->build() );
	}

	public function testValidateAnySense_yieldsSuccess() {
		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$result = $changeOp->validate( NewSense::havingId( 'S1' )->build() );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testApplyNonSense_yieldsAssertionProblem() {
		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$this->expectException( ParameterTypeException::class );
		$this->expectExceptionMessage( 'Bad value for parameter $entity' );
		$changeOp->apply( NewLexeme::create()->build() );
	}

	public function testApply_addsGlossInNewLanguage() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'de', 'pelziges Tier' )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGloss( new Term( 'en', 'furry animal' ) );
		$changeOp->apply( $sense, $summary );

		$this->assertCount( 2, $sense->getGlosses() );
		$this->assertSame( 'add-sense-glosses', $summary->getMessageKey() );
		$this->assertSame( 'en', $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-S3' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'en' => 'furry animal' ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_replacesGlossInPreexistingLanguage() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'de', 'pelziges Tier' )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGloss( new Term( 'de', 'Tier mit Pelz' ) );
		$changeOp->apply( $sense, $summary );

		$this->assertCount( 1, $sense->getGlosses() );
		$this->assertSame( 'set-sense-glosses', $summary->getMessageKey() );
		$this->assertSame( 'de', $summary->getLanguageCode() );
		$this->assertSame( [ 'L1-S3' ], $summary->getCommentArgs() );
		$this->assertSame( [ 'de' => 'Tier mit Pelz' ], $summary->getAutoSummaryArgs() );
	}

	public function testApply_noSummaryForSameTermInPreexistingLanguage() {
		$sense = NewSense::havingId( 'S3' )
			->withGloss( 'de', 'pelziges Tier' )
			->build();
		$summary = new Summary();

		$changeOp = new ChangeOpGloss( new Term( 'de', 'pelziges Tier' ) );
		$changeOp->apply( $sense, $summary );

		$this->assertCount( 1, $sense->getGlosses() );
		$this->assertNull( $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

}

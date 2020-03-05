<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api\Summary;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Api\Summary\SummaryAggregator;
use Wikibase\Lib\Summary;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Api\Summary\SummaryAggregator
 *
 * @license GPL-2.0-or-later
 */
class SummaryAggregatorTest extends TestCase {

	public function testNothingToMerge_yieldsOriginalSummary() {
		$aggregator = new SummaryAggregator( 'aggr' );

		$summary = new Summary();
		$summary->setAction( 'something' );
		$summary->setLanguage( 'en' );
		$summary->setAutoCommentArgs( [ 'a' => 'b' ] );
		$summary->setAutoSummaryArgs( [ 'c' => 'd' ] );

		$subSummary = new Summary();
		$subSummary->setAction( null );
		$subSummary->setLanguage( 'ar' );
		$subSummary->setAutoCommentArgs( [ 'e' => 'f' ] );
		$subSummary->setAutoSummaryArgs( [ 'g' => 'h' ] );

		$aggregate = $aggregator->aggregate( $summary, $subSummary );

		$this->assertSame( 'something', $aggregate->getMessageKey() );
		$this->assertSame( 'en', $aggregate->getLanguageCode() );
		$this->assertSame( [ 'a' => 'b' ], $aggregate->getCommentArgs() );
		$this->assertSame( [ 'c' => 'd' ], $aggregate->getAutoSummaryArgs() );
	}

	public function testAlreadyAggregate_yieldsOriginalSummary() {
		$aggregator = new SummaryAggregator( 'aggr' );

		$summary = new Summary();
		$summary->setAction( 'aggr' );
		$summary->setLanguage( null );
		$summary->setAutoCommentArgs( [ 'a' => 'b' ] );
		$summary->setAutoSummaryArgs( [ 'c' => 'd' ] );

		$subSummary = new Summary();
		$subSummary->setAction( 'subsomething' );
		$subSummary->setLanguage( 'ar' );
		$subSummary->setAutoCommentArgs( [ 'e' => 'f' ] );
		$subSummary->setAutoSummaryArgs( [ 'g' => 'h' ] );

		$aggregate = $aggregator->aggregate( $summary, $subSummary );

		$this->assertSame( 'aggr', $aggregate->getMessageKey() );
		$this->assertNull( $aggregate->getLanguageCode() );
		$this->assertSame( [ 'a' => 'b' ], $aggregate->getCommentArgs() );
		$this->assertSame( [ 'c' => 'd' ], $aggregate->getAutoSummaryArgs() );
	}

	public function testOriginalSummaryBlank_bubblesSubSummary() {
		$aggregator = new SummaryAggregator( 'aggr' );

		$summary = new Summary();

		$subSummary = new Summary();
		$subSummary->setAction( 'subsomething' );
		$subSummary->setLanguage( 'ar' );
		$subSummary->setAutoCommentArgs( [ 'e' => 'f' ] );
		$subSummary->setAutoSummaryArgs( [ 'g' => 'h' ] );

		$aggregate = $aggregator->aggregate( $summary, $subSummary );

		$this->assertSame( 'subsomething', $aggregate->getMessageKey() );
		$this->assertSame( 'ar', $aggregate->getLanguageCode() );
		$this->assertSame( [ 'e' => 'f' ], $aggregate->getCommentArgs() );
		$this->assertSame( [ 'g' => 'h' ], $aggregate->getAutoSummaryArgs() );
	}

	public function testAggregatingTwoDifferentActions_yieldsRespectiveMergeResult() {
		$aggregator = new SummaryAggregator( 'aggr' );

		$summary = new Summary();
		$summary->setAction( 'a' );
		$summary->setLanguage( 'en' );
		$summary->setAutoCommentArgs( [ 'lama', 'L1-F1' ] );
		$summary->setAutoSummaryArgs( [ 'ff' => 'aaa' ] );

		$subSummary = new Summary();
		$subSummary->setAction( 'b' );
		$subSummary->setLanguage( 'ar' );
		$subSummary->setAutoCommentArgs( [ 'zebra', 'L1-F1' ] );
		$subSummary->setAutoSummaryArgs( [ 'ff' => 'bbb' ] );

		$aggregate = $aggregator->aggregate( $summary, $subSummary );

		$this->assertSame( 'aggr', $aggregate->getMessageKey() );
		$this->assertNull( $aggregate->getLanguageCode() );
		$this->assertSame(
			[ 'lama', 'L1-F1', 'zebra' ],
			$aggregate->getCommentArgs()
		);
		$this->assertSame( [], $aggregate->getAutoSummaryArgs() );
	}

	public function testAggregatingTwoIdenticalActions_yieldsRespectiveMergeResult() {
		$aggregator = new SummaryAggregator( 'aggr' );

		$summary = new Summary();
		$summary->setAction( 'atomic' );
		$summary->setLanguage( 'en' );
		$summary->setAutoCommentArgs( [ 'lama', 'L1-F1' ] );
		$summary->setAutoSummaryArgs( [ 'aaa' => 'lll' ] );

		$subSummary = new Summary();
		$subSummary->setAction( 'atomic' );
		$subSummary->setLanguage( 'en' );
		$subSummary->setAutoCommentArgs( [ 'zebra', 'L1-F1' ] );
		$subSummary->setAutoSummaryArgs( [ 'bbb' => 'lll' ] );

		$aggregate = $aggregator->aggregate( $summary, $subSummary );

		$this->assertSame( 'atomic', $aggregate->getMessageKey() );
		$this->assertNull( $aggregate->getLanguageCode() );
		$this->assertSame(
			[ 'lama', 'L1-F1', 'zebra' ],
			$aggregate->getCommentArgs()
		);
		$this->assertSame(
			[ 'aaa' => 'lll', 'bbb' => 'lll' ],
			$aggregate->getAutoSummaryArgs()
		);
	}

	public function testOverrideSummary_changesSummaryByReferenceToAggregationResult() {
		$aggregator = new SummaryAggregator( 'aggr' );

		$summary = new Summary();
		$summary->setAction( 'aaa' );
		$summary->setLanguage( 'en' );
		$summary->setAutoCommentArgs( [ 'lama', 'L1-F1' ] );
		$summary->setAutoSummaryArgs( [ 'aaa' => 'lll' ] );

		$subSummary = new Summary();
		$subSummary->setAction( 'bbb' );
		$subSummary->setLanguage( 'de' );
		$subSummary->setAutoCommentArgs( [ 'zebra', 'L1-F1' ] );
		$subSummary->setAutoSummaryArgs( [ 'bbb' => 'lll' ] );

		$aggregator->overrideSummary( $summary, $subSummary );

		$this->assertSame( 'aggr', $summary->getMessageKey() );
		$this->assertNull( $summary->getLanguageCode() );
		$this->assertSame( [ 'lama', 'L1-F1', 'zebra' ], $summary->getCommentArgs() );
		$this->assertSame( [], $summary->getAutoSummaryArgs() );
	}

}

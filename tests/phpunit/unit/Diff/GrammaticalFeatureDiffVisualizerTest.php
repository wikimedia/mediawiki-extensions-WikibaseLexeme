<?php

namespace Wikibase\Lexeme\Tests\Unit\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Presentation\Diff\GrammaticalFeatureDiffVisualizer;
use Wikibase\Lexeme\Presentation\Diff\ItemReferenceDifferenceVisualizer;

/**
 * @covers \Wikibase\Lexeme\Presentation\Diff\GrammaticalFeatureDiffVisualizer
 */
class GrammaticalFeatureDiffVisualizerTest extends MediaWikiUnitTestCase {

	public function testGivenGrammaticalFeaturesChanged_oldAndNewItemsAreDisplayedWithHeader() {
		$visualizer = new GrammaticalFeatureDiffVisualizer( $this->getItemRefDiffVisualizer() );

		$diffHtml = $visualizer->visualize(
			[ 'Form', 'L123-F321', 'grammatical feature' ],
			new DiffOpChange( 'Q2', 'Q3' )
		);

		$this->assertContains( '<h1>Form / L123-F321 / grammatical feature</h1>', $diffHtml );
		$this->assertContains( '<del>Q2</del>', $diffHtml );
		$this->assertContains( '<ins>Q3</ins>', $diffHtml );
	}

	public function testGivenMultipleDiffOps_resultsAreConcatenated() {
		$visualizer = new GrammaticalFeatureDiffVisualizer( $this->getItemRefDiffVisualizer() );

		$diffHtml = $visualizer->visualize(
			[ 'Form', 'L23-F42', 'grammatical feature' ],
			new Diff( [
				new DiffOpAdd( 'Q3' ),
				new DiffOpAdd( 'Q5' ),
				new DiffOpRemove( 'Q8' ),
			] )
		);

		$this->assertContains( '<h1>Form / L23-F42 / grammatical feature</h1>', $diffHtml );
		$this->assertContains( '<h1>Form / L23-F42 / grammatical feature</h1>', $diffHtml );
		$this->assertContains( '<h1>Form / L23-F42 / grammatical feature</h1>', $diffHtml );

		$this->assertContains( '<ins>Q3</ins>', $diffHtml );
		$this->assertContains( '<ins>Q5</ins>', $diffHtml );
		$this->assertContains( '<del>Q8</del>', $diffHtml );
	}

	private function getItemRefDiffVisualizer() {
		$diffVis = $this->getMockBuilder( ItemReferenceDifferenceVisualizer::class )
			->disableOriginalConstructor()
			->getMock();

		$diffVis->method( $this->anything() )
			->willReturnCallback( function( $headerText, Diff $diff ) {
				$diffOp = $diff[0];
				$oldValue = $diffOp instanceof DiffOpAdd ? '' : $diffOp->getOldValue();
				$newValue = $diffOp instanceof DiffOpRemove ? '' : $diffOp->getNewValue();

				return "<h1>$headerText</h1><del>$oldValue</del><ins>$newValue</ins>";
			} );

		return $diffVis;
	}

}

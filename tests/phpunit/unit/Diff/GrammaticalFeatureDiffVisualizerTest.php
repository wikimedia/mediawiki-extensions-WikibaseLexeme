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
 *
 * @license GPL-2.0-or-later
 */
class GrammaticalFeatureDiffVisualizerTest extends MediaWikiUnitTestCase {

	public function testGivenGrammaticalFeaturesChanged_oldAndNewItemsAreDisplayedWithHeader() {
		$visualizer = new GrammaticalFeatureDiffVisualizer( $this->getItemRefDiffVisualizer() );

		$diffHtml = $visualizer->visualize(
			[ 'Form', 'L123-F321', 'grammatical feature' ],
			new DiffOpChange( 'Q2', 'Q3' )
		);

		$this->assertStringContainsString( '<h1>Form / L123-F321 / grammatical feature</h1>', $diffHtml );
		$this->assertStringContainsString( '<del>Q2</del>', $diffHtml );
		$this->assertStringContainsString( '<ins>Q3</ins>', $diffHtml );
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

		$this->assertStringContainsString( '<h1>Form / L23-F42 / grammatical feature</h1>', $diffHtml );
		$this->assertStringContainsString( '<h1>Form / L23-F42 / grammatical feature</h1>', $diffHtml );
		$this->assertStringContainsString( '<h1>Form / L23-F42 / grammatical feature</h1>', $diffHtml );

		$this->assertStringContainsString( '<ins>Q3</ins>', $diffHtml );
		$this->assertStringContainsString( '<ins>Q5</ins>', $diffHtml );
		$this->assertStringContainsString( '<del>Q8</del>', $diffHtml );
	}

	private function getItemRefDiffVisualizer() {
		$diffVis = $this->createMock( ItemReferenceDifferenceVisualizer::class );

		$diffVis->method( $this->anything() )
			->willReturnCallback( static function ( $headerText, Diff $diff ) {
				$diffOp = $diff[0];
				$oldValue = $diffOp instanceof DiffOpAdd ? '' : $diffOp->getOldValue();
				$newValue = $diffOp instanceof DiffOpRemove ? '' : $diffOp->getNewValue();

				return "<h1>$headerText</h1><del>$oldValue</del><ins>$newValue</ins>";
			} );

		return $diffVis;
	}

}

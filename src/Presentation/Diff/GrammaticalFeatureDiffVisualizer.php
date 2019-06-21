<?php

namespace Wikibase\Lexeme\Presentation\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;

/**
 * @license GPL-2.0-or-later
 */
class GrammaticalFeatureDiffVisualizer {

	/**
	 * @var ItemReferenceDifferenceVisualizer
	 */
	private $itemRefDiffVisualizer;

	public function __construct( ItemReferenceDifferenceVisualizer $itemRefDiffVisualizer ) {
		$this->itemRefDiffVisualizer = $itemRefDiffVisualizer;
	}

	public function visualize( array $path, DiffOp $diff ) {
		if ( $diff->isAtomic() ) {
			return $this->itemRefDiffVisualizer->visualize(
				$this->buildPathHeader( $path ),
				new Diff( [ $diff ] )
			);
		}

		$html = '';
		// @phan-suppress-next-line PhanTypeNoPropertiesForeach
		foreach ( $diff as $subOp ) {
			$html .= $this->visualize(
				$path,
				$subOp
			);
		}

		return $html;
	}

	private function buildPathHeader( $path ) {
		return implode( ' / ', $path );
	}

}

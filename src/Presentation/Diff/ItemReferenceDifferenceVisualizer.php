<?php

namespace Wikibase\Lexeme\Presentation\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Diff\DiffOpValueFormatter;

/**
 * @license GPL-2.0-or-later
 */
class ItemReferenceDifferenceVisualizer {

	/**
	 * @var EntityIdFormatter
	 */
	private $idFormatter;

	public function __construct( EntityIdFormatter $idFormatter ) {
		$this->idFormatter = $idFormatter;
	}

	public function visualize( $headerText, Diff $diff ) {
		return $this->visualizeDifference( $headerText, $diff );
	}

	private function visualizeDifference( $headerText, DiffOp $diff ) {
		if ( !$diff->isAtomic() ) {
			$html = '';
			// @phan-suppress-next-line PhanTypeNoPropertiesForeach
			foreach ( $diff as $op ) {
				$html .= $this->visualizeDifference( $headerText, $op );
			}
			return $html;
		}

		if ( $diff instanceof DiffOpChange ) {
			$valueFormatter = new DiffOpValueFormatter(
				$headerText,
				$headerText,
				$this->idFormatter->formatEntityId( $diff->getOldValue() ),
				$this->idFormatter->formatEntityId( $diff->getNewValue() )
			);
			return $valueFormatter->generateHtml();
		}
		if ( $diff instanceof DiffOpAdd ) {
			$valueFormatter = new DiffOpValueFormatter(
				'',
				$headerText,
				null,
				$this->idFormatter->formatEntityId( $diff->getNewValue() )
			);
			return $valueFormatter->generateHtml();
		}
		if ( $diff instanceof DiffOpRemove ) {
			$valueFormatter = new DiffOpValueFormatter(
				$headerText,
				'',
				$this->idFormatter->formatEntityId( $diff->getOldValue() ),
				null
			);
			return $valueFormatter->generateHtml();
		}

		return '';
	}

}

<?php

namespace Wikibase\Lexeme\Diff;

use Diff\DiffOp;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Entity\ItemId;
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
			foreach ( $diff as $op ) {
				$html .= $this->visualizeDifference( $headerText, $op );
			}
			return $html;
		}

		if ( $diff instanceof DiffOpChange ) {
			$valueFormatter = new DiffOpValueFormatter(
				$headerText,
				$headerText,
				$this->idFormatter->formatEntityId( new ItemId( $diff->getOldValue() ) ),
				$this->idFormatter->formatEntityId( new ItemId( $diff->getNewValue() ) )
			);
			return $valueFormatter->generateHtml();
		}
		if ( $diff instanceof DiffOpAdd ) {
			$valueFormatter = new DiffOpValueFormatter(
				'',
				$headerText,
				'',
				$this->idFormatter->formatEntityId( new ItemId( $diff->getNewValue() ) )
			);
			return $valueFormatter->generateHtml();
		}
		if ( $diff instanceof DiffOp\DiffOpRemove ) {
			$valueFormatter = new DiffOpValueFormatter(
				$headerText,
				'',
				$this->idFormatter->formatEntityId( new ItemId( $diff->getOldValue() ) ),
				''
			);
			return $valueFormatter->generateHtml();
		}

		return '';
	}

}

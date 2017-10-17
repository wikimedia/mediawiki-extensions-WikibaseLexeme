<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Services\Diff\StatementListDiffer;
use Wikibase\Lexeme\DataModel\Form;

/**
 * @license GPL-2.0+
 */
class FormDiffer {

	/**
	 * @var MapDiffer
	 */
	private $recursiveMapDiffer;

	/**
	 * @var StatementListDiffer
	 */
	private $statementListDiffer;

	public function __construct() {
		$this->recursiveMapDiffer = new MapDiffer( true );
		$this->statementListDiffer = new StatementListDiffer();
	}

	/**
	 * @param Form $old
	 * @param Form $new
	 *
	 * @return ChangeFormDiffOp
	 */
	public function diff( Form $old, Form $new ) {
		//TODO: Assert same ID
		$diffOps = $this->recursiveMapDiffer->doDiff(
			$this->toFormDiffArray( $old ),
			$this->toFormDiffArray( $new )
		);

		$diffOps['claim'] = $this->statementListDiffer->getDiff(
			$old->getStatements(),
			$new->getStatements()
		);

		return new ChangeFormDiffOp( $old->getId(), new Diff( $diffOps ) );
	}

	/**
	 * @param Form $form
	 *
	 * @return string[]
	 */
	private function toFormDiffArray( Form $form ) {
		$result = [];
		$result['representations'] = $form->getRepresentations()->toTextArray();
		$result['grammaticalFeatures'] = $form->getGrammaticalFeatures();

		return $result;
	}

}

<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;

/**
 * @license GPL-2.0+
 */
interface FormDiff extends DiffOp {

	/**
	 * @return Diff
	 */
	public function getRepresentationDiff();

	public function getGrammaticalFeaturesDiff();

	public function getStatementsDiff();

}

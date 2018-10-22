<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;

/**
 * @license GPL-2.0-or-later
 */
interface FormDiff extends DiffOp {

	/**
	 * @return Diff
	 */
	public function getRepresentationDiff();

	public function getGrammaticalFeaturesDiff();

	public function getStatementsDiff();

}

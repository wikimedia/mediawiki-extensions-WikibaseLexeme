<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;

/**
 * @license GPL-2.0-or-later
 */
interface SenseDiff extends DiffOp {

	/**
	 * @return Diff
	 */
	public function getGlossesDiff();

	public function getStatementsDiff();

}

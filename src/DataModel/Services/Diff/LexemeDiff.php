<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Wikibase\DataModel\Services\Diff\EntityDiff;

/**
 * Represents a diff between two lexemes.
 *
 * @since 1.0
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDiff extends EntityDiff {

	/**
	 * @param DiffOp[] $operations
	 */
	public function __construct( array $operations = [] ) {
		$this->fixSubstructureDiff( $operations, 'lemmas' );
		$this->fixSubstructureDiff( $operations, 'claim' );

		parent::__construct( $operations, true );
	}

	/**
	 * Returns a Diff object with the lemma differences.
	 *
	 * @return Diff
	 */
	public function getLemmasDiff() {
		return isset( $this['lemmas'] ) ? $this['lemmas'] : new Diff( [], true );
	}

	/**
	 * Returns if there are any changes (equivalent to: any differences between the entities).
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->getLemmasDiff()->isEmpty()
		       && $this->getClaimsDiff()->isEmpty();
	}

}

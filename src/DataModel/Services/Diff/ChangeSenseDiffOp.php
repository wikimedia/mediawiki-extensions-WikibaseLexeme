<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use LogicException;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class ChangeSenseDiffOp extends EntityDiff implements SenseDiff {

	/**
	 * @var SenseId
	 */
	private $senseId;
	/**
	 * @var Diff
	 */
	private $diffOps;

	public function __construct( SenseId $senseId, Diff $diffOps ) {
		$this->senseId = $senseId;
		// FIXME: This class already extends Diff. It should not require an other Diff object.
		$this->diffOps = $diffOps;
	}

	/**
	 * @return SenseId
	 */
	public function getSenseId() {
		return $this->senseId;
	}

	/**
	 * @return Diff
	 */
	public function getGlossesDiff() {
		return isset( $this->diffOps['glosses'] ) ?
			$this->diffOps['glosses']
			: new Diff( [] );
	}

	/**
	 * @return Diff
	 */
	public function getStatementsDiff() {
		return isset( $this->diffOps['claim'] ) ?
			$this->diffOps['claim']
			: new Diff( [] );
	}

	public function serialize() {
		throw new LogicException( "serialize() is not implemented" );
	}

	public function unserialize( $serialized ) {
		throw new LogicException( "unserialize() is not implemented" );
	}

	public function getType() {
		return 'diff';
	}

	public function isAtomic() {
		return false;
	}

	public function toArray( $valueConverter = null ) {
		throw new LogicException( "toArray() is not implemented" );
	}

	/**
	 * @see Diff::count
	 *
	 * @return int
	 */
	public function count() {
		return $this->diffOps->count();
	}

	/**
	 * @see Diff::isEmpty
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->diffOps->isEmpty();
	}

	public function getOperations() {
		// Due to the way this DiffOp is structured the default implementation would return nothing
		throw new LogicException( "getOperations() is not implemented" );
	}

	public function getArrayCopy() {
		// Due to the way this DiffOp is structured the default implementation would return nothing
		throw new LogicException( "getArrayCopy() is not implemented" );
	}

}

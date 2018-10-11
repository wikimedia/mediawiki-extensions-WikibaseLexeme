<?php

namespace Wikibase\Lexeme\Domain\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use LogicException;
use Wikibase\Lexeme\Domain\DataModel\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveSenseDiff implements SenseDiff {

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
		$this->diffOps = $diffOps;
	}

	public function getRemovedSenseId() {
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
		throw new LogicException( 'toArray() is not implemented' );
	}

	public function count() {
		return $this->diffOps->count();
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

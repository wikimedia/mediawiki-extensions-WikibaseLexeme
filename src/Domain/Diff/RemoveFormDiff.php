<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\DiffOp\Diff\Diff;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveFormDiff implements FormDiff {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var Diff
	 */
	private $diffOps;

	public function __construct( FormId $formId, Diff $diffOps ) {
		$this->formId = $formId;
		$this->diffOps = $diffOps;
	}

	public function getRemovedFormId() {
		return $this->formId;
	}

	/**
	 * @return Diff
	 */
	public function getRepresentationDiff() {
		return $this->diffOps['representations'] ?? new Diff( [] );
	}

	/**
	 * @return Diff
	 */
	public function getGrammaticalFeaturesDiff() {
		return $this->diffOps['grammaticalFeatures'] ?? new Diff( [] );
	}

	/**
	 * @return Diff
	 */
	public function getStatementsDiff() {
		return $this->diffOps['claim'] ?? new Diff( [] );
	}

	public function serialize() {
		throw new \LogicException( "serialize() is not implemented" );
	}

	public function unserialize( $serialized ) {
		throw new \LogicException( "unserialize() is not implemented" );
	}

	public function getType() {
		return 'diff';
	}

	public function isAtomic() {
		return false;
	}

	public function toArray( $valueConverter = null ) {
		throw new \LogicException( 'toArray() is not implemented' );
	}

	public function count() {
		return $this->diffOps->count();
	}

	public function getOperations() {
		// Due to the way this DiffOp is structured the default implementation would return nothing
		throw new \LogicException( "getOperations() is not implemented" );
	}

	public function getArrayCopy() {
		// Due to the way this DiffOp is structured the default implementation would return nothing
		throw new \LogicException( "getArrayCopy() is not implemented" );
	}

}

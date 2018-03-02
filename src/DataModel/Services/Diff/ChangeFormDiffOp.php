<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\Lexeme\DataModel\FormId;

/**
 * @license GPL-2.0-or-later
 */
class ChangeFormDiffOp extends EntityDiff implements FormDiff {

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
		// FIXME: This class already extends Diff. It should note require an other Diff object.
		$this->diffOps = $diffOps;
	}

	/**
	 * @return FormId
	 */
	public function getFormId() {
		return $this->formId;
	}

	/**
	 * @return Diff
	 */
	public function getRepresentationDiff() {
		return isset( $this->diffOps['representations'] ) ?
			$this->diffOps['representations']
			: new Diff( [] );
	}

	/**
	 * @return Diff
	 */
	public function getGrammaticalFeaturesDiff() {
		return isset( $this->diffOps['grammaticalFeatures'] ) ?
			$this->diffOps['grammaticalFeatures']
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
	}

	public function unserialize( $serialized ) {
	}

	public function getType() {
//		return 'diff/lexeme/form';
		return 'diff';
	}

	public function isAtomic() {
		return false;
	}

	public function toArray( $valueConverter = null ) {
		throw new \LogicException( "toArray() is not implemented" );
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

}

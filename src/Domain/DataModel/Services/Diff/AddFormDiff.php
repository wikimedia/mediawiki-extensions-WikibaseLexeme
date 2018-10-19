<?php

namespace Wikibase\Lexeme\Domain\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Wikibase\Lexeme\Domain\DataModel\Form;

/**
 * @license GPL-2.0-or-later
 */
class AddFormDiff implements FormDiff {

	/**
	 * @var Form
	 */
	private $form;

	/**
	 * @var Diff
	 */
	private $diffOps;

	public function __construct( Form $addedForm, Diff $diffOps ) {
		$this->form = $addedForm;
		$this->diffOps = $diffOps;
	}

	public function getAddedForm() {
		return $this->form;
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

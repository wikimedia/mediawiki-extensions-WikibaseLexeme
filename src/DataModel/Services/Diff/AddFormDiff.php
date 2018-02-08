<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Wikibase\Lexeme\DataModel\Form;

/**
 * @license GPL-2.0+
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
	}

	public function unserialize( $serialized ) {
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

}

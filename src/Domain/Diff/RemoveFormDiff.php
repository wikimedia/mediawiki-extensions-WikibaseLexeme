<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\DiffOp\Diff\Diff;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 * @phan-file-suppress PhanPluginNeverReturnMethod
 */
class RemoveFormDiff implements FormDiff {

	use Unserializable;

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

	public function getType(): string {
		return 'diff';
	}

	public function isAtomic(): bool {
		return false;
	}

	public function toArray( callable $valueConverter = null ): array {
		throw new \LogicException( 'toArray() is not implemented' );
	}

	public function count(): int {
		return $this->diffOps->count();
	}

	public function getOperations(): array {
		// Due to the way this DiffOp is structured the default implementation would return nothing
		throw new \LogicException( "getOperations() is not implemented" );
	}

	public function getArrayCopy(): array {
		// Due to the way this DiffOp is structured the default implementation would return nothing
		throw new \LogicException( "getArrayCopy() is not implemented" );
	}

}

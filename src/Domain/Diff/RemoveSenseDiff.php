<?php

namespace Wikibase\Lexeme\Domain\Diff;

use Diff\DiffOp\Diff\Diff;
use LogicException;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @license GPL-2.0-or-later
 * @phan-file-suppress PhanPluginNeverReturnMethod
 */
class RemoveSenseDiff implements SenseDiff {

	use Unserializable;

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
		return $this->diffOps['glosses'] ?? new Diff( [] );
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
		throw new LogicException( 'toArray() is not implemented' );
	}

	public function count(): int {
		return $this->diffOps->count();
	}

	public function getOperations(): array {
		// Due to the way this DiffOp is structured the default implementation would return nothing
		throw new LogicException( "getOperations() is not implemented" );
	}

	public function getArrayCopy(): array {
		// Due to the way this DiffOp is structured the default implementation would return nothing
		throw new LogicException( "getArrayCopy() is not implemented" );
	}

}

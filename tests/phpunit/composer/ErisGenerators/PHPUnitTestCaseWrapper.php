<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @license GPL-2.0-or-later
 */
class PHPUnitTestCaseWrapper {

	use TestTrait {
		limitTo as private traitLimitTo;
		minimumEvaluationRatio as private traitMinimumEvaluationRatio;
		shrinkingTimeLimit as private traitShrinkingTimeLimit;
		withRand as private traitWithRand;
		seedingRandomNumberGeneration as private traitSeedingRandomNumberGeneration;
		dumpSeedForReproducing as private traitDumpSeedForReproducing;
	}

	/**
	 * @var TestCase
	 */
	private $testCase;

	public function __construct( TestCase $testCase ) {
		$this->testCase = $testCase;
	}

	/** @inheritDoc */
	public function __call( $name, $arguments ) {
		return $this->testCase->$name( ...$arguments );
	}

	/** @inheritDoc */
	public function seed( $seed ) {
		$this->seed = $seed;
		return $this;
	}

	/** @inheritDoc */
	public function limitTo( $limit ) {
		return $this->traitLimitTo( $limit );
	}

	/** @inheritDoc */
	public function minimumEvaluationRatio( $ratio ) {
		return $this->traitMinimumEvaluationRatio( $ratio );
	}

	/** @inheritDoc */
	public function shrinkingTimeLimit( $shrinkingTimeLimit ) {
		return $this->traitShrinkingTimeLimit( $shrinkingTimeLimit );
	}

	/** @inheritDoc */
	public function withRand( $randFunction ) {
		return $this->traitWithRand( $randFunction );
	}

	/** @inheritDoc */
	public function seedingRandomNumberGeneration() {
		return $this->traitSeedingRandomNumberGeneration();
	}

	/** @inheritDoc */
	public function dumpSeedForReproducing() {
		return $this->traitDumpSeedForReproducing();
	}

}

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

	public function __call( $name, $arguments ) {
		return call_user_func_array( [ $this->testCase, $name ], $arguments );
	}

	public function seed( $seed ) {
		$this->seed = $seed;
		return $this;
	}

	public function limitTo( $limit ) {
		return $this->traitLimitTo( $limit );
	}

	public function minimumEvaluationRatio( $ratio ) {
		return $this->traitMinimumEvaluationRatio( $ratio );
	}

	public function shrinkingTimeLimit( $shrinkingTimeLimit ) {
		return $this->traitShrinkingTimeLimit( $shrinkingTimeLimit );
	}

	public function withRand( $randFunction ) {
		return $this->traitWithRand( $randFunction );
	}

	public function seedingRandomNumberGeneration() {
		return $this->traitSeedingRandomNumberGeneration();
	}

	public function dumpSeedForReproducing() {
		return $this->traitDumpSeedForReproducing();
	}

}

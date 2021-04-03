<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Facade;

/**
 * Helper trait to simplify Eris usage in Mediawiki PHPUnit tests
 *
 * IMPORTANT: This trait can only be applied to \PHPUnit\Framework\TestCase
 *
 * @license GPL-2.0-or-later
 */
trait ErisTest {

	private $testCaseWrapper;

	protected function eris() {
		$this->skipTestIfErisIsNotInstalled();

		$this->testCaseWrapper = new PHPUnitTestCaseWrapper( $this );

		$this->testCaseWrapper->seedingRandomNumberGeneration();
		$this->testCaseWrapper->minimumEvaluationRatio( 0.5 );

		return $this->testCaseWrapper;
	}

	/**
	 * @after
	 */
	public function erisTearDown() {
		if ( !self::erisIsInstalled() ) {
			return;
		}

		if ( $this->testCaseWrapper ) {
			$this->testCaseWrapper->dumpSeedForReproducing();
		}
	}

	protected function skipTestIfErisIsNotInstalled() {
		if ( !self::erisIsInstalled() ) {
			$this->markTestSkipped( 'Package `giorgiosironi/eris` is not installed. Skipping' );
		}
	}

	/**
	 * @return bool
	 */
	private static function erisIsInstalled() {
		return class_exists( Facade::class );
	}

}

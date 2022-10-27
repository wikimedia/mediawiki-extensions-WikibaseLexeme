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

	private static bool $erisIsInstalled = false;

	private ?PHPUnitTestCaseWrapper $testCaseWrapper = null;

	/** @beforeClass */
	public static function erisSetUpBeforeClass() {
		self::$erisIsInstalled = class_exists( Facade::class );

		if ( !self::$erisIsInstalled ) {
			return;
		}

		PHPUnitTestCaseWrapper::erisSetupBeforeClass();
	}

	/** @before */
	public function erisSetUp() {
		if ( !self::$erisIsInstalled ) {
			return;
		}

		$this->testCaseWrapper = new PHPUnitTestCaseWrapper( $this );
		$this->testCaseWrapper->erisSetup();
	}

	protected function eris() {
		$this->skipTestIfErisIsNotInstalled();

		$this->testCaseWrapper->seedingRandomNumberGeneration();
		$this->testCaseWrapper->minimumEvaluationRatio( 0.5 );

		return $this->testCaseWrapper;
	}

	/**
	 * @after
	 */
	public function erisTearDown() {
		if ( !self::$erisIsInstalled ) {
			return;
		}

		$this->testCaseWrapper->erisTeardown();
		$this->testCaseWrapper = null;
	}

	protected function skipTestIfErisIsNotInstalled() {
		if ( !self::$erisIsInstalled ) {
			$this->markTestSkipped( 'Package `giorgiosironi/eris` is not installed. Skipping' );
		}
	}

}

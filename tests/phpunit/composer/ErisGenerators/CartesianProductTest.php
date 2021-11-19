<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator\GeneratedValueOptions;
use Eris\Generator\GeneratedValueSingle;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Wikibase\Lexeme\Tests\ErisGenerators\CartesianProduct
 *
 * @license GPL-2.0-or-later
 */
class CartesianProductTest extends TestCase {

	use ErisTest;

	public function testCanHandleOneGeneratedValueOptions() {
		$this->skipTestIfErisIsNotInstalled();

		$opt1 = $this->createOptionsWithSingleValue( 1 );

		/** @var GeneratedValueOptions $result */
		$result = CartesianProduct::create( $opt1 )
			->map(
				static function ( $n1 ) {
					return $n1;
				}
			);

		$this->assertSame( 1, $result->count() );
		$this->assertSame( 1, $result->first()->unbox() );
	}

	public function testCanHandleTwoGeneratedValueOptions() {
		$this->skipTestIfErisIsNotInstalled();

		$opt1 = $this->createOptionsWithSingleValue( 1 );
		$opt2 = $this->createOptionsWithSingleValue( 2 );

		/** @var GeneratedValueOptions $result */
		$result = CartesianProduct::create( $opt1, $opt2 )
			->map(
				static function ( $n1, $n2 ) {
					return [ $n1, $n2 ];
				}
			);

		$this->assertInstanceOf( GeneratedValueOptions::class, $result );
		$this->assertSame( 1, $result->count() );
		$this->assertSame( [ 1, 2 ], $result->first()->unbox() );
	}

	public function testCanHandleThreeGeneratedValueOptions() {
		$this->skipTestIfErisIsNotInstalled();

		$opt1 = $this->createOptionsWithSingleValue( 1 );
		$opt2 = $this->createOptionsWithSingleValue( 2 );
		$opt3 = $this->createOptionsWithSingleValue( 3 );

		/** @var GeneratedValueOptions $result */
		$result = CartesianProduct::create( $opt1, $opt2, $opt3 )
			->map(
				static function ( $n1, $n2, $n3 ) {
					return [ $n1, $n2, $n3 ];
				}
			);

		$this->assertSame( 1, $result->count() );
		$this->assertSame( [ 1, 2, 3 ], $result->first()->unbox() );
	}

	public function testCanHandleGeneratedValueSingle() {
		$this->skipTestIfErisIsNotInstalled();

		$opt1 = GeneratedValueSingle::fromJustValue( 1 );
		$opt2 = GeneratedValueSingle::fromJustValue( 2 );

		/** @var GeneratedValueOptions $result */
		$result = CartesianProduct::create( $opt1, $opt2 )
			->map(
				static function ( $n1, $n2 ) {
					return [ $n1, $n2 ];
				}
			);

		$this->assertInstanceOf( GeneratedValueOptions::class, $result );
		$this->assertSame( 1, $result->count() );
		$this->assertSame( [ 1, 2 ], $result->first()->unbox() );
	}

	public function testCanCombineValuesFromDifferentGenerators() {
		$this->skipTestIfErisIsNotInstalled();

		$opt1 = GeneratedValueSingle::fromJustValue( 1, 'generator a' );
		$opt2 = GeneratedValueSingle::fromJustValue( 2, 'generator b' );

		/** @var GeneratedValueOptions $result */
		$result = CartesianProduct::create( $opt1, $opt2 )
			->map(
				static function ( $n1, $n2 ) {
					return [ $n1, $n2 ];
				}
			);

		$this->assertSame( 1, $result->count() );
		$this->assertSame( [ 1, 2 ], $result->first()->unbox() );
	}

	public function testProducesAllTheCombinations() {
		$this->skipTestIfErisIsNotInstalled();

		$opt1 = new GeneratedValueOptions( [
				GeneratedValueSingle::fromJustValue( 1 ),
				GeneratedValueSingle::fromJustValue( 2 ),
				GeneratedValueSingle::fromJustValue( 3 ),
		] );
		$opt2 = new GeneratedValueOptions( [
				GeneratedValueSingle::fromJustValue( 4 ),
				GeneratedValueSingle::fromJustValue( 5 ),
				GeneratedValueSingle::fromJustValue( 6 ),
		] );

		/** @var GeneratedValueOptions $result */
		$resultingCalls = [];
		$result = CartesianProduct::create( $opt1, $opt2 )
			->map(
				static function ( $n1, $n2 ) use ( &$resultingCalls ) {
					$resultingCalls[] = [ $n1, $n2 ];
					return [ $n1, $n2 ];
				}
			);

		$this->assertEquals( 9, $result->count() );
		$this->assertContains( [ 1,4 ], $resultingCalls );
		$this->assertContains( [ 1,5 ], $resultingCalls );
		$this->assertContains( [ 1,6 ], $resultingCalls );
		$this->assertContains( [ 2,4 ], $resultingCalls );
		$this->assertContains( [ 2,5 ], $resultingCalls );
		$this->assertContains( [ 2,6 ], $resultingCalls );
		$this->assertContains( [ 3,4 ], $resultingCalls );
		$this->assertContains( [ 3,5 ], $resultingCalls );
		$this->assertContains( [ 3,6 ], $resultingCalls );
	}

	/**
	 * @return GeneratedValueOptions
	 */
	private function createOptionsWithSingleValue( $value ) {
		return new GeneratedValueOptions( [
				GeneratedValueSingle::fromJustValue( $value )
		] );
	}

}

<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator\GeneratedValueOptions;
use Eris\Generator\GeneratedValueSingle;

/**
 * @license GPL-2.0+
 */
class CartesianProduct {

	/**
	 * @var GeneratedValueOptions[]
	 */
	private $generatedValues;

	/**
	 * @param GeneratedValueOptions|GeneratedValueSingle $generatedValue
	 * @param GeneratedValueOptions|GeneratedValueSingle $_generatedValue
	 *
	 * @return CartesianProduct
	 */
	public static function create( $generatedValue /*, ...$_generatedValue*/ ) {
		$args = func_get_args();

		$resultingValues = array_map(
			function ( $generatedValue ) {
				if ( $generatedValue instanceof GeneratedValueSingle ) {
					return new GeneratedValueOptions( [ $generatedValue ] );
				} elseif ( $generatedValue instanceof GeneratedValueOptions ) {
					return $generatedValue;
				} else {
					throw new \InvalidArgumentException(
						'$generatedValue can only be GeneratedValueOptions or GeneratedValueSingle'
					);
				}
			},
			$args
		);

		return new self( $resultingValues );
	}

	private function __construct( array $generatedValues ) {
		$this->generatedValues = $generatedValues;
	}

	public function map( callable $fn, $generatorName = 'CartesianProduct' ) {
		$result = $this->combine( $generatorName );

		return $result->map(
			function ( array $args ) use ( $fn ) {
				return call_user_func_array( $fn, $args );
			},
			$generatorName
		);
	}

	/**
	 * @param $generatorName
	 *
	 * @return GeneratedValueOptions
	 */
	private function combine( $generatorName ) {
		if ( count( $this->generatedValues ) === 1 ) {
			return $this->generatedValues[0]->map(
				function ( $v ) {
					return [ $v ];
				},
				$generatorName
			);
		}

		$result = $this->cartesianProduct(
			$this->generatedValues[0],
			$this->generatedValues[1],
			$generatorName,
			function ( $v1, $v2 ) {
				return [ $v1, $v2 ];
			}
		);

		$valuesCount = count( $this->generatedValues );
		for ( $i = 2; $i < $valuesCount; $i++ ) {
			$result = $this->cartesianProduct(
				$result,
				$this->generatedValues[$i],
				$generatorName,
				function ( array $args, $value ) {
					$args[] = $value;
					return $args;
				}
			);
		}

		return $result;
	}

	private function cartesianProduct(
		GeneratedValueOptions $v1,
		GeneratedValueOptions $v2,
		$generatorName,
		callable $merge
	) {
		$options = [];
		foreach ( $v1 as $firstPart ) {
			foreach ( $v2 as $secondPart ) {
				$options[] = GeneratedValueSingle::fromValueAndInput(
					$merge( $firstPart->unbox(), $secondPart->unbox() ),
					$merge( $firstPart->input(), $secondPart->input() ),
					$generatorName
				);
			}
		}
		return new GeneratedValueOptions( $options );
	}

}

<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator\GeneratedValueOptions;
use Eris\Generator\GeneratedValueSingle;

/**
 * @license GPL-2.0-or-later
 */
class CartesianProduct {

	/**
	 * @var GeneratedValueOptions[]
	 */
	private $generatedValues;

	/**
	 * @param array ...$args an array of GeneratedValueSingle | GeneratedValueOptions
	 *
	 * @return self
	 */
	public static function create( ...$args ) {

		$resultingValues = array_map(
			static function ( $generatedValue ) {
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
			static function ( array $args ) use ( $fn ) {
				return call_user_func_array( $fn, $args );
			},
			$generatorName
		);
	}

	/**
	 * @param string $generatorName
	 *
	 * @return GeneratedValueOptions
	 */
	private function combine( $generatorName ) {
		if ( count( $this->generatedValues ) === 1 ) {
			return $this->generatedValues[0]->map(
				static function ( $v ) {
					return [ $v ];
				},
				$generatorName
			);
		}

		$result = $this->cartesianProduct(
			$this->generatedValues[0],
			$this->generatedValues[1],
			$generatorName,
			static function ( $v1, $v2 ) {
				return [ $v1, $v2 ];
			}
		);

		$valuesCount = count( $this->generatedValues );
		for ( $i = 2; $i < $valuesCount; $i++ ) {
			$result = $this->cartesianProduct(
				$result,
				$this->generatedValues[$i],
				$generatorName,
				static function ( array $args, $value ) {
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

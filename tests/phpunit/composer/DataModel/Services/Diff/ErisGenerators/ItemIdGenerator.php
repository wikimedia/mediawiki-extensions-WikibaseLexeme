<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ChooseGenerator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0+
 */
class ItemIdGenerator implements Generator {

	/**
	 * @var Generator
	 */
	private $chooseGenerator;

	public function __construct() {
		$this->chooseGenerator = new ChooseGenerator( 1, 2147483647 );
	}

	/**
	 * @param $size $size
	 * @param $rand $rand
	 *
	 * @return GeneratedValueSingle
	 */
	public function generate( $size, $rand ) {
		return call_user_func( $this, $size, $rand );
	}

	/**
	 * @see Generator::__invoke
	 *
	 * @param int $size
	 * @param callable $rand
	 *
	 * @return GeneratedValueSingle<T>
	 */
	public function __invoke( $size, $rand ) {
		$chooseGenerator = $this->chooseGenerator;
		/** @var GeneratedValueSingle $value */
		$value = $chooseGenerator( $size, $rand );
		return $value->map(
			function ( $value ) {
				return new ItemId( 'Q' . $value );
			},
			'itemId'
		);
	}

	/**
	 * @see Generator::shrink
	 *
	 * @param GeneratedValueSingle<T> $element
	 *
	 * @return GeneratedValueSingle<T>|GeneratedValueOptions<T>
	 */
	public function shrink( GeneratedValueSingle $element ) {
		return $element;
	}

	/**
	 * @param GeneratedValueSingle $element
	 *
	 * @return bool
	 */
	public function contains( GeneratedValueSingle $element ) {
		return $element->unbox() instanceof ItemId;
	}

}

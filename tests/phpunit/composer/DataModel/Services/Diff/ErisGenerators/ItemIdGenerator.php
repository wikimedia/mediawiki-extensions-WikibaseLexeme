<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ChooseGenerator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\DataModel\Entity\ItemId;

class ItemIdGenerator implements Generator {

	private $chooseGenerator;

	/**
	 * ItemIdGenerator constructor.
	 */
	public function __construct() {
		$this->chooseGenerator = new ChooseGenerator( 1, 2147483647 );
	}

	/**
	 * @param $size
	 * @param $rand
	 * @return GeneratedValueSingle
	 */
	public function generate( $size, $rand ) {
		return call_user_func( $this, $size, $rand );
	}

	/**
	 * @param int The generation size
	 * @param callable  a rand() function
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
	 * The conditions for terminating are either:
	 * - returning the same GeneratedValueSingle passed in
	 * - returning an empty GeneratedValueOptions
	 *
	 * @param GeneratedValueSingle<T>
	 * @return GeneratedValueSingle<T>|GeneratedValueOptions<T>
	 */
	public function shrink( GeneratedValueSingle $element ) {
		return $element;
	}

	/**
	 * @param GeneratedValueSingle
	 * @return bool
	 */
	public function contains( GeneratedValueSingle $element ) {
		return $element->unbox() instanceof ItemId;
	}

}

<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ChooseGenerator;
use Eris\Generator\GeneratedValue;
use Eris\Generator\GeneratedValueSingle;
use Eris\Random\RandomRange;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemIdGenerator implements Generator {
	private const MAX_ITEM_ID = 2147483647;

	/**
	 * @var Generator
	 */
	private $numericItemIdGenerator;

	public function __construct( $maxItemId = self::MAX_ITEM_ID ) {
		$this->numericItemIdGenerator = new ChooseGenerator( 1, $maxItemId );
	}

	/**
	 * @see Generator::__invoke
	 *
	 * @param int $size
	 * @param RandomRange $rand
	 *
	 * @return GeneratedValueSingle<T>
	 */
	public function __invoke( $size, RandomRange $rand ) {
		$generateNumericItemId = $this->numericItemIdGenerator;

		/** @var GeneratedValueSingle $generatedValue */
		$generatedValue = $generateNumericItemId( $size, $rand );
		return $generatedValue->map(
			static function ( $numericId ) {
				return new ItemId( 'Q' . $numericId );
			},
			'itemId'
		);
	}

	/**
	 * @see Generator::shrink
	 *
	 * @param GeneratedValue<T> $element
	 *
	 * @return GeneratedValue<T>
	 */
	public function shrink( GeneratedValue $element ) {
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

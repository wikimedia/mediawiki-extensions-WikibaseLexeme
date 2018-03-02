<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ChooseGenerator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemIdGenerator implements Generator {
	const MAX_ITEM_ID = 2147483647;

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
	 * @param callable $rand
	 *
	 * @return GeneratedValueSingle<T>
	 */
	public function __invoke( $size, $rand ) {
		$generateNumericItemId = $this->numericItemIdGenerator;

		/** @var GeneratedValueSingle $generatedValue */
		$generatedValue = $generateNumericItemId( $size, $rand );
		return $generatedValue->map(
			function ( $numericId ) {
				return new ItemId( 'Q' . $numericId );
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

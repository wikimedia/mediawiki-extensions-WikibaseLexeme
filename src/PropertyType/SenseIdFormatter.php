<?php

namespace Wikibase\Lexeme\PropertyType;

use DataValues\StringValue;
use ValueFormatters\ValueFormatter;

/**
 * @license GPL-2.0-or-later
 */
class SenseIdFormatter implements ValueFormatter {

	/**
	 * @param StringValue $value
	 *
	 * @return string HTML
	 */
	public function format( $value ) {
		return $value->serialize();
	}

}

<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use DomainException;
use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;

/**
 * @license GPL-2.0-or-later
 */
class LanguageCodeGenerator implements Generator {

	/**
	 * @see Generator::__invoke
	 *
	 * @param int $size
	 * @param callable $rand
	 *
	 * @return GeneratedValueSingle<T>
	 */
	public function __invoke( $size, $rand ) {
		$length = $rand( 2, 3 );
		$built = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$built .= chr( $rand( ord( 'a' ), ord( 'z' ) ) );
		}

		return GeneratedValueSingle::fromJustValue( $built, 'languageCode' );
	}

	/**
	 * @see Generator::shrink
	 *
	 * @param GeneratedValueSingle<T> $element
	 *
	 * @return GeneratedValueSingle<T>|GeneratedValueOptions<T>
	 */
	public function shrink( GeneratedValueSingle $element ) {
		if ( !$this->contains( $element ) ) {
			throw new DomainException(
				'Cannot shrink ' . $element . ' because it does not belong ' .
				'to the domain of the Strings.'
			);
		}

		if ( strlen( $element->unbox() ) <= 2 ) {
			return $element;
		}

		return GeneratedValueSingle::fromJustValue(
			substr( $element->unbox(), 0, -1 ),
			'languageCode'
		);
	}

	/**
	 * @param GeneratedValueSingle $element
	 *
	 * @return bool
	 */
	public function contains( GeneratedValueSingle $element ) {
		if ( !is_string( $element->unbox() ) ) {
			return false;
		}

		$aChar = ord( 'a' );
		$zChar = ord( 'z' );

		foreach ( str_split( $element->unbox() ) as $char ) {
			if ( $char < $aChar || $char > $zChar ) {
				return false;
			}
		}

		return true;
	}

}

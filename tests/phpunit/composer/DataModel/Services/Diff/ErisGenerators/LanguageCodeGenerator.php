<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

use DomainException;
use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;

class LanguageCodeGenerator implements Generator {

	/**
	 * @param int The generation size
	 * @param callable  a rand() function
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
	 * The conditions for terminating are either:
	 * - returning the same GeneratedValueSingle passed in
	 * - returning an empty GeneratedValueOptions
	 *
	 * @param GeneratedValueSingle<T>
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
	 * @param GeneratedValueSingle
	 * @return bool
	 */
	public function contains( GeneratedValueSingle $element ) {
		$aChar = ord( 'a' );
		$zChar = ord( 'z' );
		if ( !is_string( $element->unbox() ) ) {
			return false;
		}

		foreach ( str_split( $element->unbox() ) as $char ) {
			if ( $char < $aChar || $char > $zChar ) {
				return false;
			}
		}
		return true;
	}

}

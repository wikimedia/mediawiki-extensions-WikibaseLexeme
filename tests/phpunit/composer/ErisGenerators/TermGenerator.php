<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValue;
use Eris\Generator\GeneratedValueSingle;
use Eris\Generator\StringGenerator;
use Eris\Random\RandomRange;
use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 */
class TermGenerator implements Generator {

	/**
	 * @var Generator
	 */
	private $termLanguageGenerator;

	/**
	 * @var Generator
	 */
	private $termTextGenerator;

	public function __construct() {
		$this->termLanguageGenerator = new LanguageCodeGenerator();
		$this->termTextGenerator = new StringGenerator();
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
		$generateTermLanguage = $this->termLanguageGenerator;
		$generateTermText = $this->termTextGenerator;

		$languageCode = $generateTermLanguage( 3, $rand )->unbox();
		do {
			$text = $generateTermText( $size + 1, $rand )->unbox();
		} while ( $text === '' );

		return GeneratedValueSingle::fromJustValue( new Term( $languageCode, $text ), 'term' );
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
		return $element->unbox() instanceof Term;
	}

}

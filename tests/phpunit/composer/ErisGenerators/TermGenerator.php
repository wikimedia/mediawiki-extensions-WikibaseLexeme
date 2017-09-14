<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;
use Eris\Generator\StringGenerator;
use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0+
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
	 * @param callable $rand
	 *
	 * @return GeneratedValueSingle<T>
	 */
	public function __invoke( $size, $rand ) {
		$generateTermLanguage = $this->termLanguageGenerator;
		$generateTermText = $this->termTextGenerator;

		$languageCode = $generateTermLanguage( 3, $rand )->unbox();
		$text = $generateTermText( $size, $rand )->unbox();
		return GeneratedValueSingle::fromJustValue( new Term( $languageCode, $text ), 'term' );
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
		return $element->unbox() instanceof Term;
	}

}

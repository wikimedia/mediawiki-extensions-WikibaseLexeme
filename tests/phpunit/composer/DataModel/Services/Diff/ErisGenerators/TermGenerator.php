<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

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
	private $termValueGenerator;

	/**
	 * @var Generator
	 */
	private $termLanguageGenerator;

	public function __construct() {
		$this->termValueGenerator = new StringGenerator();
		$this->termLanguageGenerator = new LanguageCodeGenerator();
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
		$languageGenerator = $this->termLanguageGenerator;
		$valueGenerator = $this->termValueGenerator;

		$languageCode = $languageGenerator( 3, $rand )->unbox();
		$text = $valueGenerator( $size, $rand )->unbox();
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

<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\DataModel\Term\Term;

class TermGenerator implements Generator {

	/**
	 * @var Generator\StringGenerator
	 */
	private $termValueGenerator;
	/**
	 * @var Generator\StringGenerator
	 */
	private $termLanguageGenerator;

	public function __construct() {
		$this->termValueGenerator = new Generator\StringGenerator();
		$this->termLanguageGenerator = new LanguageCodeGenerator();
	}

	/**
	 * @param int The generation size
	 * @param callable  a rand() function
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
		return $element->unbox() instanceof Term;
	}

}

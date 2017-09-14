<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0+
 */
class LexemeGenerator implements Generator {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var Generator
	 */
	private $languageGenerator;

	/**
	 * @var Generator
	 */
	private $lexicalCategoryGenerator;

	/**
	 * @var Generator
	 */
	private $lemmaListGenerator;

	public function __construct( LexemeId $lexemeId ) {
		$this->lexemeId = $lexemeId;

		$this->lemmaListGenerator = new TermListGenerator();
		$this->languageGenerator = new ItemIdGenerator();
		$this->lexicalCategoryGenerator = new ItemIdGenerator();
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
		$language = $this->languageGenerator->generate( $size, $rand )->unbox();
		$lexicalCategory = $this->lexicalCategoryGenerator->generate( $size, $rand )->unbox();
		$lemmas = $this->lemmaListGenerator->__invoke( $size, $rand )->unbox();

		$lexeme = new Lexeme( $this->lexemeId, $lemmas, $lexicalCategory, $language );
		return GeneratedValueSingle::fromJustValue( $lexeme, 'lexeme' );
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
		return $element->unbox() instanceof Lexeme;
	}

}

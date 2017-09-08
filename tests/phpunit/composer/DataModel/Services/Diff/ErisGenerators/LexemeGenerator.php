<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;

class LexemeGenerator implements Generator {
	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var ItemIdGenerator
	 */
	private $languageGenerator;

	/**
	 * @var ItemIdGenerator
	 */
	private $lexicalCategoryGenerator;

	/**
	 * @var TermListGenerator
	 */
	private $lemmaListGenerator;

	public function __construct( LexemeId $lexemeId ) {
		$this->lexemeId = $lexemeId;

		$this->lemmaListGenerator = new TermListGenerator();
		$this->languageGenerator = new ItemIdGenerator();
		$this->lexicalCategoryGenerator = new ItemIdGenerator();
	}

	/**
	 * @param int The generation size
	 * @param callable  a rand() function
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
		return $element->unbox() instanceof Lexeme;
	}

}

<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueOptions;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\FormSet;
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

	/**
	 * @var FormSetGenerator
	 */
	private $formSetGenerator;

	public function __construct( LexemeId $lexemeId ) {
		$this->lexemeId = $lexemeId;

		$this->languageGenerator = new ItemIdGenerator();
		$this->lexicalCategoryGenerator = new ItemIdGenerator();
		$this->lemmaListGenerator = new TermListGenerator();
		$this->formSetGenerator = new FormSetGenerator();
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
		$size = min( $size, 25 );

		$generateLanguage = $this->languageGenerator;
		$generateLexicalCategory = $this->lexicalCategoryGenerator;
		$generateLemmaList = $this->lemmaListGenerator;
		$generateFormSet = $this->formSetGenerator;

		$language = $generateLanguage( $size, $rand )->unbox();
		$lexicalCategory = $generateLexicalCategory( $size, $rand )->unbox();
		$lemmas = $generateLemmaList( $size, $rand )->unbox();

		/** @var FormSet $formSet */
		$formSet = $generateFormSet( $size, $rand )->unbox();
		$counterIncrement = $rand( 1, 50 );

		$nextFormId = $formSet->maxFormIdNumber() + $counterIncrement;
		$lexeme = new Lexeme(
			$this->lexemeId,
			$lemmas,
			$lexicalCategory,
			$language,
			null,
			$nextFormId,
			$formSet->toArray()
		);
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

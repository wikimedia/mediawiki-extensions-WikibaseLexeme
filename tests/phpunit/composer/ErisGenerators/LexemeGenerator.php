<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ChooseGenerator;
use Eris\Generator\ConstantGenerator;
use Eris\Generator\GeneratedValue;
use Eris\Generator\GeneratedValueSingle;
use Eris\Generator\MapGenerator;
use Eris\Random\RandomRange;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class LexemeGenerator implements Generator {

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

	/**
	 * @var Generator
	 */
	private $lexemeIdGenerator;

	public function __construct( LexemeId $lexemeId = null ) {
		if ( $lexemeId ) {
			$this->lexemeIdGenerator = new ConstantGenerator( $lexemeId );
		} else {
			$this->lexemeIdGenerator = new MapGenerator(
				static function ( $number ) {
					return new LexemeId( 'L' . $number );
				},
				new ChooseGenerator( 1, Int32EntityId::MAX )
			);
		}

		$this->languageGenerator = new ItemIdGenerator();
		$this->lexicalCategoryGenerator = new ItemIdGenerator();
		$this->lemmaListGenerator = new TermListGenerator();
		$this->formSetGenerator = new FormSetGenerator();
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
		$size = min( $size, 10 );

		$generateLexemeId = $this->lexemeIdGenerator;
		$generateLanguage = $this->languageGenerator;
		$generateLexicalCategory = $this->lexicalCategoryGenerator;
		$generateLemmaList = $this->lemmaListGenerator;
		$generateFormSet = $this->formSetGenerator;

		$lexemeId = $generateLexemeId( $size, $rand )->unbox();
		$language = $generateLanguage( $size, $rand )->unbox();
		$lexicalCategory = $generateLexicalCategory( $size, $rand )->unbox();
		$lemmas = $generateLemmaList( $size, $rand )->unbox();

		/** @var FormSet $formSet */
		$formSet = $generateFormSet( $size, $rand )->unbox();
		$counterIncrement = $rand->rand( 1, 50 );

		$nextFormId = $formSet->maxFormIdNumber() + $counterIncrement;
		$lexeme = new Lexeme(
			$lexemeId,
			$lemmas,
			$lexicalCategory,
			$language,
			null,
			$nextFormId,
			$formSet
		);
		return GeneratedValueSingle::fromJustValue( $lexeme, 'lexeme' );
	}

	/**
	 * @see Generator::shrink
	 *
	 * @param GeneratedValue<T> $element
	 *
	 * @return GeneratedValue<T>
	 */
	public function shrink( GeneratedValue $element ) {
		/** @var Lexeme $lexeme */
		$lexeme = $element->unbox();

		$shrunkFormSet = $this->formSetGenerator->shrink(
			GeneratedValueSingle::fromJustValue( $lexeme->getForms() )
		);
		$shrunkLemmas = $this->lemmaListGenerator->shrink(
			GeneratedValueSingle::fromJustValue( $lexeme->getLemmas() )
		);

		return CartesianProduct::create( $shrunkLemmas, $shrunkFormSet )->map(
			static function ( TermList $lemmas, FormSet $formSet ) {
				return new Lexeme(
					new LexemeId( 'L1' ),
					$lemmas,
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' ),
					null,
					$formSet->maxFormIdNumber() + 1,
					$formSet
				);
			},
			'lexeme'
		);
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

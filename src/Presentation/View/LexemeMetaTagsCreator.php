<?php

namespace Wikibase\Lexeme\Presentation\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\View\EntityMetaTagsCreator;
use Wikimedia\Assert\Assert;

/**
 * Class for creating meta tags (i.e. title and description) for Lexemes
 * @license GPL-2.0-or-later
 */
class LexemeMetaTagsCreator implements EntityMetaTagsCreator {

	private $lemmaSeparator;
	private $labelDescriptionLookup;

	/**
	 * @param string $lemmaSeparator
	 * @param LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup
	 */
	public function __construct(
		$lemmaSeparator,
		LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup
	) {
		Assert::parameterType( 'string', $lemmaSeparator, '$lemmaSeparator' );

		$this->lemmaSeparator = $lemmaSeparator;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return array
	 */
	public function getMetaTags( EntityDocument $entity ) : array {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */
		$metaTags = [
			'title' => $this->getTitleText( $entity ),
			'og:title' => $this->getTitleText( $entity ),
			'twitter:card' => 'summary'
		];

		$description = $this->getDescriptionText( $entity );
		if ( !empty( $description ) ) {
			$metaTags [ 'description' ] = $description;
			$metaTags [ 'og:description' ] = $description;

		}

		return $metaTags;
	}

	/**
	 * @param Lexeme $entity
	 *
	 * @return null|string
	 */
	private function getTitleText( Lexeme $entity ) {
		$lemmas = $entity->getLemmas()->toTextArray();
		if ( empty( $lemmas ) ) {
			return $entity->getId()->getSerialization();
		}
		return implode( $this->lemmaSeparator, $lemmas );
	}

	private function getDescriptionText( Lexeme $entity ) {
		if ( !$entity->getLanguage() || !$entity->getLexicalCategory() ) {
			return '';
		}

		$langauge = $this->labelDescriptionLookup->getLabel( $entity->getLanguage() );
		$category = $this->labelDescriptionLookup->getLabel( $entity->getLexicalCategory() );

		if ( !$langauge || !$category ) {
			return '';
		}

		return $langauge->getText() . ' ' . $category->getText();
	}

}

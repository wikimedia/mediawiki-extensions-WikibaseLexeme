<?php

namespace Wikibase\Lexeme\Presentation\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
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
	 * @param FallbackLabelDescriptionLookup $labelDescriptionLookup
	 */
	public function __construct(
		$lemmaSeparator,
		FallbackLabelDescriptionLookup $labelDescriptionLookup
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
	public function getMetaTags( EntityDocument $entity ): array {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */
		$metaTags = [
			'title' => $this->getTitleText( $entity ),
			'og:title' => $this->getTitleText( $entity ),
			'twitter:card' => 'summary'
		];

		$description = $this->getDescriptionText( $entity );
		if ( !empty( $description ) ) {
			$metaTags[ 'description' ] = $description;
			$metaTags[ 'og:description' ] = $description;

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
		$language = $this->labelDescriptionLookup->getLabel( $entity->getLanguage() );
		$category = $this->labelDescriptionLookup->getLabel( $entity->getLexicalCategory() );

		if ( !$language || !$category ) {
			return '';
		}

		return $language->getText() . ' ' . $category->getText();
	}

}

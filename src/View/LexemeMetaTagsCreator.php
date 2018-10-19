<?php

namespace Wikibase\Lexeme\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\DataModel\Lexeme;
use Wikibase\View\EntityMetaTagsCreator;
use Wikimedia\Assert\Assert;

/**
 * Class for creating meta tags (i.e. title and description) for Lexemes
 * @license GPL-2.0-or-later
 */
class LexemeMetaTagsCreator implements EntityMetaTagsCreator {

	private $lemmaSeparator;

	/**
	 * @param string $lemmaSeparator
	 */
	public function __construct( $lemmaSeparator ) {
		Assert::parameterType( 'string', $lemmaSeparator, '$lemmaSeparator' );

		$this->lemmaSeparator = $lemmaSeparator;
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
		];

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

}

<?php

namespace Wikibase\Lexeme\Presentation\Formatters;

use Html;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0-or-later
 */
class LexemeIdHtmlFormatter implements EntityIdFormatter {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;
	/**
	 * @var NonExistingEntityIdHtmlFormatter
	 */
	private $nonExistingFormatter;

	public function __construct(
		EntityLookup $entityLookup,
		LabelDescriptionLookup $labelDescriptionLookup,
		EntityTitleLookup $titleLookup,
		LocalizedTextProvider $textProvider,
		NonExistingEntityIdHtmlFormatter $nonExistingEntityIdHtmlFormatter
	) {
		// TODO: This formatter should not load entire entities.
		$this->entityLookup = $entityLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->titleLookup = $titleLookup;
		$this->textProvider = $textProvider;
		$this->nonExistingFormatter = $nonExistingEntityIdHtmlFormatter;
	}

	public function formatEntityId( EntityId $id ) {
		if ( !$id instanceof LexemeId ) {
			throw new InvalidArgumentException( 'Not a lexeme ID: ' . $id->getSerialization() );
		}

		/** @var Lexeme $lexeme */
		$lexeme = $this->entityLookup->getEntity( $id );
		'@phan-var Lexeme $lexeme';

		if ( $lexeme === null ) {
			// msg: wikibaselexeme-deletedentity-lexeme
			return $this->nonExistingFormatter->formatEntityId( $id );
		}

		$lemmas = $lexeme->getLemmas();

		$linkLabel = $this->buildLinkLabel( $lemmas );

		$title = $this->titleLookup->getTitleForId( $id );
		$url = $title->isLocal() ? $title->getLocalURL() : $title->getFullURL();

		$attributes = [
			'href' => $url,
			'title' => $this->buildLinkTitle( $id, $lexeme->getLanguage(), $lexeme->getLexicalCategory() )
		];
		return Html::rawElement( 'a', $attributes, $linkLabel );
	}

	/**
	 * @param TermList $lemmas
	 *
	 * @return string HTML
	 */
	private function buildLinkLabel( TermList $lemmas ) {
		return implode(
			Html::element( 'span', [], $this->textProvider->get(
				'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma'
			) ),
			$this->formatLemmas( $lemmas )
		);
	}

	/**
	 * @param TermList $lemmas
	 *
	 * @return string[] HTML elements
	 */
	private function formatLemmas( TermList $lemmas ) {
		$elements = [];
		foreach ( $lemmas->toTextArray() as $languageCode => $lemma ) {
			$elements[] = Html::element(
				'span',
				[ 'lang' => \LanguageCode::bcp47( $languageCode ) ],
				$lemma
			);
		}
		return $elements;
	}

	/**
	 * @param LexemeId $id
	 * @param ItemId $languageId
	 * @param ItemId $lexicalCategoryId
	 *
	 * @return string Plain text
	 */
	private function buildLinkTitle( LexemeId $id, ItemId $languageId, ItemId $lexicalCategoryId ) {
		$languageLabel = $this->getLabel( $languageId );
		$lexicalCategoryLabel = $this->getLabel( $lexicalCategoryId );

		$titleContent = $this->textProvider->get(
			'wikibaselexeme-presentation-lexeme-secondary-label',
			[ $languageLabel, $lexicalCategoryLabel ]
		);

		return $this->textProvider->get(
			'wikibaselexeme-lexeme-link-title',
			[ $id->getSerialization(), $titleContent ]
		);
	}

	/**
	 * Gets the item's label or falls back to its id serialization
	 * @param ItemId $id
	 * @return string
	 */
	private function getLabel( ItemId $id ) {
		$label = $this->labelDescriptionLookup->getLabel( $id );
		return $label ? $label->getText() : $id->getSerialization();
	}

}

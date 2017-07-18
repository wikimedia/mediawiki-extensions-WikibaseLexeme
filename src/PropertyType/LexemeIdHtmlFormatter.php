<?php

namespace Wikibase\Lexeme\PropertyType;

use Html;
use InvalidArgumentException;
use Language;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @note This class is not tested and assumed to be temporary solution for demo purposes
 * @deprecated Will be gone some time later
 */
class LexemeIdHtmlFormatter implements ValueFormatter {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var Language
	 */
	private $language;

	public function __construct(
		EntityLookup $entityLookup,
		EntityTitleLookup $entityTitleLookup
	) {
		$this->entityLookup = $entityLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->language = Language::factory( 'en' );
	}

	/**
	 * @param mixed $value
	 *
	 * @return string
	 * @throws FormattingException
	 */
	public function format( $value ) {
		if ( !( $value instanceof EntityIdValue ) ) {
			throw new InvalidArgumentException(
				'Data value type mismatch. Expected an EntityIdValue.'
			);
		}

		if ( !( $value->getEntityId() instanceof LexemeId ) ) {
			throw new InvalidArgumentException(
				'Data value type mismatch. Expected an EntityIdValue referencing Lexeme.'
			);
		}

		/** @var LexemeId $lexemeId */
		$lexemeId = $value->getEntityId();
		/** @var Lexeme $lexeme */
		$lexeme = $this->entityLookup->getEntity( $lexemeId );

		$title = $this->entityTitleLookup->getTitleForId( $lexemeId );

		if ( $title === null ) {
			return $this->getHtmlForNonExistent( $lexemeId );
		}

		$url = $title->isLocal() ? $title->getLocalURL() : $title->getFullURL();

		$label = $this->buildLabel( $lexeme );
		$attributes = [
			'href' => $url
		];

		return Html::element( 'a', $attributes, $label );
	}

	/**
	 * @param LexemeId $lexemeId
	 *
	 * @return string HTML
	 */
	private function getHtmlForNonExistent( LexemeId $lexemeId ) {
		$attributes = [ 'class' => 'wb-entity-undefinedinfo' ];

		$message = wfMessage(
			'parentheses',
			wfMessage( 'wikibase-deletedentity-lexeme' )->text()
		);

		$undefinedInfo = Html::element( 'span', $attributes, $message );

		$separator = wfMessage( 'word-separator' )->text();
		return $lexemeId->getSerialization() . $separator . $undefinedInfo;
	}

	private function buildLabel( Lexeme $lexeme ) {
		$label = '';

		$glossTexts = [];
		foreach ( $lexeme->getLemmas() as $lemma ) {
			$glossTexts[] = $lemma->getText();
		}

		$label .= $this->language->commaList( $glossTexts );
		$label .= ' ';
		$label .= wfMessage(
			'parentheses',
			$lexeme->getId()->getSerialization()
		);
		$label .= ' ';

		/** @var Item $languageItem */
		$languageItem = $this->entityLookup->getEntity( $lexeme->getLanguage() );
		/** @var Item $lexicalCategoryItem */
		$lexicalCategoryItem = $this->entityLookup->getEntity( $lexeme->getLexicalCategory() );

		$languageCode = $this->language->getCode();
		// 'noun in English'
		// TODO: Rethink way to present 'noun in English' - it will not look correct in Russian
		//       because language word should be in the genitive which currently is not possible
		$label .= wfMessage(
			'wikibase-lexeme-view-language-lexical-category',
			[
				$lexicalCategoryItem->getLabels()->getByLanguage( $languageCode )->getText(),
				$languageItem->getLabels()->getByLanguage( $languageCode )->getText()
			]
		);

		return $label;
	}

}

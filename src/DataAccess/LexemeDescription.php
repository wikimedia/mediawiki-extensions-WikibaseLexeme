<?php

namespace Wikibase\Lexeme\DataAccess;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;

/**
 * Class for generating Lexeme description strings
 *
 * @license GPL-2.0-or-later
 */
class LexemeDescription {
	/**
	 * @var LabelDescriptionLookup
	 */
	private $lookup;
	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * Display language
	 * @var Language
	 */
	private $displayLanguage;

	public function __construct(
		LabelDescriptionLookup $lookup,
		EntityIdParser $idParser,
		Language $displayLanguage
	) {
		$this->lookup = $lookup;
		$this->idParser = $idParser;
		$this->displayLanguage = $displayLanguage;
	}

	/**
	 * Get label or return empty string.
	 * @param EntityId|null $id
	 * @param string $default Default value if unable to retrieve label
	 * @return string Label or "" if does not exist.
	 */
	public function getLabelOrDefault(
		EntityId $id = null,
		$default = ""
	) {
		if ( !$id ) {
			return $default;
		}
		$label = $this->lookup->getLabel( $id );
		if ( !$label ) {
			return $default;
		}
		return $label->getText();
	}

	/**
	 * Create short lexeme description, e.g.: "German noun" or "English verb"
	 * Currently not uses the ID, may change later
	 * @param EntityId $id Lexeme ID
	 * @param string $language Language ID, as string
	 * @param string $category Lexical category ID, as string
	 * @return string
	 * @throws \MWException
	 */
	public function createDescription( EntityId $id, $language, $category ) {
		$languageId = self::parseOrNull( $language, $this->idParser );
		$categoryId = self::parseOrNull( $category, $this->idParser );
		return wfMessage( 'wikibaselexeme-description' )
			->inLanguage( $this->displayLanguage )
			->params(
				$this->getLabelOrDefault( $languageId,
					wfMessage( 'wikibaselexeme-unknown-language' )
						->inLanguage( $this->displayLanguage )
						->text() ),
				$this->getLabelOrDefault( $categoryId,
					wfMessage( 'wikibaselexeme-unknown-category' )
						->inLanguage( $this->displayLanguage )
						->text() )
			)->text();
	}

	/**
	 * Create Form descriptions, along the lines of:
	 * singular genitive for Leiter (L1): German noun
	 *
	 * @param EntityId $lexemeId Main lexeme
	 * @param EntityId[] $features Form feature IDs list
	 * @param string $lemma Lexeme's lemma
	 * @param string $language Language ID, as string
	 * @param string $category Lexical category ID, as string
	 * @return string
	 * @throws \MWException
	 */
	public function createFormDescription(
		EntityId $lexemeId, array $features, $lemma, $language, $category
	) {
		$lemmaDescription = $this->createDescription( $lexemeId, $language, $category );
		// Create list of feature labels, should match what FormsView.php is doing
		$comma = wfMessage( 'comma-separator' )->inLanguage( $this->displayLanguage )->text();
		$featuresString = implode( $comma, array_filter( array_map(
			function ( EntityId $featureId ) {
				// TODO: do we need separate string for this?
				return $this->getLabelOrDefault( $featureId,
					wfMessage( 'wikibaselexeme-unknown-category' )
						->inLanguage( $this->displayLanguage )->text() );
			}, $features ) ) );
		if ( empty( $featuresString ) ) {
			$featuresString = wfMessage( 'wikibaselexeme-no-features' )
				->inLanguage( $this->displayLanguage )->text();
		}
		return wfMessage( 'wikibaselexeme-form-description' )
			->inLanguage( $this->displayLanguage )
			->params(
				$featuresString,
				$lemma,
				$lexemeId->getSerialization(),
				$lemmaDescription
			)->text();
	}

	/**
	 * Parse entity ID or return null
	 * @param string $text
	 * @param EntityIdParser $idParser
	 * @return null|EntityId
	 */
	public static function parseOrNull( $text, EntityIdParser $idParser ) {
		try {
			$id = $idParser->parse( $text );
		} catch ( EntityIdParsingException $ex ) {
			return null;
		}
		return $id;
	}

}

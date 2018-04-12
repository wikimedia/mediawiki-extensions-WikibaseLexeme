<?php

namespace Wikibase\Lexeme\Search;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Repo\Search\Elastic\EntitySearchUtils;

/**
 * Class for generating Lexeme description strings
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
	 * @param EntityId $id
	 * @param string $default Default value if unable to retrieve label
	 * @return string Label or "" if does not exist.
	 */
	private function getLabelOrDefault(
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
		$languageId = EntitySearchUtils::parseOrNull( $language, $this->idParser );
		$categoryId = EntitySearchUtils::parseOrNull( $category, $this->idParser );
		return wfMessage( 'wikibaselexeme-description' )
			->inLanguage( $this->displayLanguage )
			->params(
				$this->getLabelOrDefault( $languageId, wfMessage( 'wikibaselexeme-unknown-language' )
						->inLanguage( $this->displayLanguage )
						->text() ),
				$this->getLabelOrDefault( $categoryId, wfMessage( 'wikibaselexeme-unknown-category' )
						->inLanguage( $this->displayLanguage )
						->text() )
			)->text();
	}

}

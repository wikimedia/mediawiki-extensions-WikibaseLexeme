<?php

namespace Wikibase\Lexeme\Formatters;

use OutOfBoundsException;
use OutOfRangeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0-or-later
 */
class SenseIdTextFormatter implements EntityIdFormatter {

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var LocalizedTextProvider
	 */
	private $localizedTextProvider;

	public function __construct(
		EntityRevisionLookup $revisionLookup,
		LocalizedTextProvider $localizedTextProvider
	) {
		$this->revisionLookup = $revisionLookup;
		$this->localizedTextProvider = $localizedTextProvider;
	}

	/**
	 * @param SenseId $value
	 *
	 * @return string plain text
	 */
	public function formatEntityId( EntityId $value ) {
		$lexemeRevision = $this->revisionLookup->getEntityRevision( $value->getLexemeId() );

		if ( $lexemeRevision === null ) {
			return $value->getSerialization();
		}

		/** @var Lexeme $lexeme */
		$lexeme = $lexemeRevision->getEntity();
		try {
			$sense = $lexeme->getSense( $value );
		} catch ( OutOfRangeException $e ) {
			return $value->getSerialization();
		}

		$lemmas = implode(
			$this->localizedTextProvider->get(
				'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma'
			),
			$lexeme->getLemmas()->toTextArray()
		);
		try {
			$gloss = $sense->getGlosses()->getByLanguage(
				$this->localizedTextProvider->getLanguageOf( 'wikibaselexeme-senseidformatter-layout' )
			)->getText(); // TODO language fallbacks (T200983)
		} catch ( OutOfBoundsException $e ) {
			$gloss = 'TODO';
		}

		return $this->localizedTextProvider->get(
			'wikibaselexeme-senseidformatter-layout',
			[ $lemmas, $gloss ]
		);
	}

}

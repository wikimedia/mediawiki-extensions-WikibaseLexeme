<?php

namespace Wikibase\Lexeme\Presentation\Formatters;

use Html;
use OutOfRangeException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0-or-later
 */
class SenseIdHtmlFormatter implements EntityIdFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var LocalizedTextProvider
	 */
	private $localizedTextProvider;

	/**
	 * @var TermLanguageFallbackChain
	 */
	private $termLanguageFallbackChain;

	/**
	 * @var LanguageFallbackIndicator
	 */
	private $languageFallbackIndicator;

	public function __construct(
		EntityTitleLookup $titleLookup,
		EntityRevisionLookup $revisionLookup,
		LocalizedTextProvider $localizedTextProvider,
		TermLanguageFallbackChain $termLanguageFallbackChain,
		LanguageFallbackIndicator $languageFallbackIndicator
	) {
		$this->titleLookup = $titleLookup;
		$this->revisionLookup = $revisionLookup;
		$this->localizedTextProvider = $localizedTextProvider;
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
		$this->languageFallbackIndicator = $languageFallbackIndicator;
	}

	/**
	 * @param SenseId $value
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $value ) {
		$title = $this->titleLookup->getTitleForId( $value );

		try {
			$lexemeRevision = $this->revisionLookup->getEntityRevision( $value->getLexemeId() );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			$lexemeRevision = null; // see fallback below
		}

		if ( $lexemeRevision === null ) {
			return $this->getTextWrappedInLink( $value->getSerialization(), $title );
		}

		/** @var Lexeme $lexeme */
		$lexeme = $lexemeRevision->getEntity();
		'@phan-var Lexeme $lexeme';
		try {
			$sense = $lexeme->getSense( $value );
		} catch ( OutOfRangeException $e ) {
			return $this->getTextWrappedInLink( $value->getSerialization(), $title );
		}

		$lemmas = implode(
			$this->localizedTextProvider->get(
				'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma'
			),
			$lexeme->getLemmas()->toTextArray()
		);

		$languageCode = $this->localizedTextProvider->getLanguageOf(
			'wikibaselexeme-senseidformatter-layout'
		);
		$glossArray = $sense->getGlosses()->toTextArray();
		$preferredGloss = $this->termLanguageFallbackChain->extractPreferredValue( $glossArray );
		if ( $preferredGloss === null ) {
			return $this->getTextWrappedInLink( $value->getSerialization(), $title );
		}

		$glossFallback = new TermFallback(
			$languageCode,
			$preferredGloss['value'],
			$preferredGloss['language'],
			$preferredGloss['source']
		);

		$text = $this->localizedTextProvider->get(
			'wikibaselexeme-senseidformatter-layout',
			[ $lemmas, $glossFallback->getText() ]
		);

		return $this->getTextWrappedInLink( $text, $title ) .
			$this->languageFallbackIndicator->getHtml( $glossFallback );
	}

	private function getTextWrappedInLink( $text, Title $title ) {
		return Html::element(
			'a',
			[
				'href' => $title->isLocal() ? $title->getLinkURL() : $title->getFullURL(),
			],
			$text
		);
	}

}

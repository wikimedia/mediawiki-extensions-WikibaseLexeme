<?php

namespace Wikibase\Lexeme\Presentation\Formatters;

use Html;
use MediaWiki\Languages\LanguageFactory;
use OutOfRangeException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\RawMessageParameter;

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

	/**
	 * @var LanguageFactory
	 */
	private $languageFactory;

	public function __construct(
		EntityTitleLookup $titleLookup,
		EntityRevisionLookup $revisionLookup,
		LocalizedTextProvider $localizedTextProvider,
		TermLanguageFallbackChain $termLanguageFallbackChain,
		LanguageFallbackIndicator $languageFallbackIndicator,
		LanguageFactory $languageFactory
	) {
		$this->titleLookup = $titleLookup;
		$this->revisionLookup = $revisionLookup;
		$this->localizedTextProvider = $localizedTextProvider;
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
		$this->languageFallbackIndicator = $languageFallbackIndicator;
		$this->languageFactory = $languageFactory;
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

		return $this->getTextWrappedInLink(
				$this->buildSenseLinkContents( $lexeme->getLemmas(), $glossFallback ),
				$title
			) . $this->languageFallbackIndicator->getHtml( $glossFallback );
	}

	private function getTextWrappedInLink( string $linkContents, Title $title ) {
		return Html::rawElement(
			'a',
			[
				'href' => $title->isLocal() ? $title->getLinkURL() : $title->getFullURL(),
			],
			$linkContents
		);
	}

	private function buildSenseLinkContents( TermList $lemmas, TermFallback $gloss ): string {
		return $this->localizedTextProvider->getEscaped(
			'wikibaselexeme-senseidformatter-layout',
			[
				new RawMessageParameter( implode(
					$this->localizedTextProvider->getEscaped(
						'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma'
					),
					$this->buildLemmasMarkup( $lemmas )
				) ),
				new RawMessageParameter( $this->buildGlossMarkup( $gloss ) )
			]
		);
	}

	private function buildGlossMarkup( TermFallback $gloss ) {
		$language = $this->languageFactory->getLanguage( $gloss->getActualLanguageCode() );
		return Html::element(
			'span',
			[
				'lang' => $language->getHtmlCode(),
				'dir' => $language->getDir(),
			],
			$gloss->getText()
		);
	}

	private function buildLemmasMarkup( TermList $lemmas ): array {
		return array_map( function ( Term $lemma ) {
			$language = $this->languageFactory->getLanguage( $lemma->getLanguageCode() );
			return Html::element(
				'span',
				[
					'lang' => $language->getHtmlCode(),
					'dir' => $language->getDir(),
				],
				$lemma->getText()
			);
		}, iterator_to_array( $lemmas->getIterator() ) );
	}

}

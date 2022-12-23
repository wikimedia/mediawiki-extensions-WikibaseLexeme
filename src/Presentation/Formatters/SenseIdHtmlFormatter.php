<?php

declare( strict_types = 1 );

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

	private EntityTitleLookup $titleLookup;
	private EntityRevisionLookup $revisionLookup;
	private LocalizedTextProvider $localizedTextProvider;
	private TermLanguageFallbackChain $termLanguageFallbackChain;
	private LanguageFallbackIndicator $languageFallbackIndicator;
	private LanguageFactory $languageFactory;
	private EntityIdFormatter $entityIdLabelFormatter;

	public function __construct(
		EntityTitleLookup $titleLookup,
		EntityRevisionLookup $revisionLookup,
		LocalizedTextProvider $localizedTextProvider,
		TermLanguageFallbackChain $termLanguageFallbackChain,
		LanguageFallbackIndicator $languageFallbackIndicator,
		LanguageFactory $languageFactory,
		EntityIdFormatter $entityIdLabelFormatter
	) {
		$this->titleLookup = $titleLookup;
		$this->revisionLookup = $revisionLookup;
		$this->localizedTextProvider = $localizedTextProvider;
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
		$this->languageFallbackIndicator = $languageFallbackIndicator;
		$this->languageFactory = $languageFactory;
		$this->entityIdLabelFormatter = $entityIdLabelFormatter;
	}

	/**
	 * @param SenseId $value
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $value ): string {
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

		$lexemeLanguageLabel = $this->entityIdLabelFormatter->formatEntityId( $lexeme->getLanguage() );
		$glossArray = $sense->getGlosses()->toTextArray();
		$preferredGloss = $this->termLanguageFallbackChain->extractPreferredValue( $glossArray );

		if ( $preferredGloss !== null ) {
			$languageCode = $this->localizedTextProvider->getLanguageOf(
				'wikibaselexeme-senseidformatter-layout'
			);
			$glossFallback = new TermFallback(
				$languageCode,
				$preferredGloss['value'],
				$preferredGloss['language'],
				$preferredGloss['source']
			);
			$linkContents = $this->buildSenseLinkContents( $lexeme->getLemmas(), $lexemeLanguageLabel, $glossFallback );
			$suffix = $this->languageFallbackIndicator->getHtml( $glossFallback );
		} else {
			$linkContents = $value->getSerialization();
			$suffix = '';
		}

		return $this->getTextWrappedInLink( $linkContents, $title ) . $suffix;
	}

	private function getTextWrappedInLink( string $linkContents, Title $title ): string {
		return Html::rawElement(
			'a',
			[
				'href' => $title->isLocal() ? $title->getLinkURL() : $title->getFullURL(),
			],
			$linkContents
		);
	}

	private function buildSenseLinkContents(
		TermList $lemmas,
		string $languageLabel,
		TermFallback $gloss
	): string {
		return $this->localizedTextProvider->getEscaped(
			'wikibaselexeme-senseidformatter-layout',
			[
				new RawMessageParameter( implode(
					$this->localizedTextProvider->getEscaped(
						'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma'
					),
					$this->buildLemmasMarkup( $lemmas )
				) ),
				new RawMessageParameter( $this->buildGlossMarkup( $gloss ) ),
				$languageLabel,
			]
		);
	}

	private function buildGlossMarkup( TermFallback $gloss ): string {
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

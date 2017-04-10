<?php

namespace Wikibase\Lexeme\View;

use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeViewFactory {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $fallbackChain;

	/**
	 * @var EditSectionGenerator
	 */
	private $editSectionGenerator;

	/**
	 * @var EntityTermsView
	 */
	private $entityTermsView;

	/**
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 * @param EntityTermsView $entityTermsView
	 */
	public function __construct(
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator,
		EntityTermsView $entityTermsView
	) {
		$this->languageCode = $languageCode;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->fallbackChain = $fallbackChain;
		$this->editSectionGenerator = $editSectionGenerator;
		$this->entityTermsView = $entityTermsView;
	}

	public function newLexemeView() {
		$templates = include __DIR__ . '/../../resources/templates.php';
		$languageDirectionalityLookup = new MediaWikiLanguageDirectionalityLookup();
		$localizedTextProvider = new MediaWikiLocalizedTextProvider( $this->languageCode );

		$formsView = new LexemeFormsView(
			$localizedTextProvider,
			new LexemeTemplateFactory( $templates )
		);
		$sensesView = new SensesView( $localizedTextProvider );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$statementSectionsView = $wikibaseRepo->getViewFactory()->newStatementSectionsView(
			$this->languageCode,
			$this->labelDescriptionLookup,
			$this->fallbackChain,
			$this->editSectionGenerator
		);

		$htmlTermRenderer = new FallbackHintHtmlTermRenderer(
			$languageDirectionalityLookup,
			new LanguageNameLookup( $this->languageCode )
		);

		return new LexemeView(
			TemplateFactory::getDefaultInstance(),
			$this->entityTermsView,
			$languageDirectionalityLookup,
			$this->languageCode,
			$formsView,
			$sensesView,
			$statementSectionsView,
			$htmlTermRenderer,
			$this->labelDescriptionLookup
		);
	}

}

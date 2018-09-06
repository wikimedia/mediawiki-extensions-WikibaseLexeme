<?php

namespace Wikibase\Lexeme\View;

use Language;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lexeme\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\EntityTermsView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
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
	 * @var EntityIdFormatterFactory
	 */
	private $entityIdFormatterFactory;

	/**
	 * @param string $languageCode
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param EditSectionGenerator $editSectionGenerator
	 * @param EntityTermsView $entityTermsView
	 * @param EntityIdFormatterFactory $entityIdFormatterFactory
	 */
	public function __construct(
		$languageCode,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChain $fallbackChain,
		EditSectionGenerator $editSectionGenerator,
		EntityTermsView $entityTermsView,
		EntityIdFormatterFactory $entityIdFormatterFactory
	) {
		$this->languageCode = $languageCode;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->fallbackChain = $fallbackChain;
		$this->editSectionGenerator = $editSectionGenerator;
		$this->entityTermsView = $entityTermsView;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
	}

	public function newLexemeView() {
		$templates = include __DIR__ . '/../../resources/templates.php';
		$templateFactory = new LexemeTemplateFactory( $templates );

		$languageDirectionalityLookup = new MediaWikiLanguageDirectionalityLookup();
		$localizedTextProvider = new MediaWikiLocalizedTextProvider( $this->languageCode );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		// TODO: $this->labelDescriptionLookup is an EntityInfo based lookup that only knows
		// entities processed via EntityParserOutputDataUpdater first, which processes statements
		// and sitelinks only and does not know about Lexeme-specific concepts like lexical category
		// and language.
		$retrievingLabelDescriptionLookup = $wikibaseRepo
			->getLanguageFallbackLabelDescriptionLookupFactory()
			->newLabelDescriptionLookup( Language::factory( $this->languageCode ) );

		$statementSectionsView = $wikibaseRepo->getViewFactory()->newStatementSectionsView(
			$this->languageCode,
			$this->labelDescriptionLookup,
			$this->fallbackChain,
			$this->editSectionGenerator
		);

		$statementGroupListView = $wikibaseRepo->getViewFactory()->newStatementGroupListView(
			$this->languageCode,
			$retrievingLabelDescriptionLookup,
			$this->fallbackChain,
			$this->editSectionGenerator
		);

		$idLinkFormatter = $this->entityIdFormatterFactory
			->getEntityIdFormatter( $retrievingLabelDescriptionLookup );

		$formsView = new FormsView(
			$localizedTextProvider,
			$templateFactory,
			$idLinkFormatter,
			$statementGroupListView
		);

		$sensesView = new SensesView(
			$localizedTextProvider,
			$languageDirectionalityLookup,
			$templateFactory,
			$statementGroupListView,
			$this->languageCode
		);

		return new LexemeView(
			TemplateFactory::getDefaultInstance(),
			$this->entityTermsView,
			$languageDirectionalityLookup,
			$this->languageCode,
			$formsView,
			$sensesView,
			$statementSectionsView,
			new LexemeTermFormatter(
				$localizedTextProvider
					->get( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
			),
			$idLinkFormatter
		);
	}

}

<?php

namespace Wikibase\Lexeme\Presentation\View;

use Language;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\LanguageFallbackChain;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoTermLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class LexemeViewFactory {

	/**
	 * @var LanguageFallbackChain
	 */
	private $fallbackChain;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var EntityInfo
	 */
	private $entityInfo;

	/**
	 * @var string
	 */
	private $saveMessageKey;

	public function __construct(
		Language $language,
		LanguageFallbackChain $fallbackChain,
		EntityInfo $entityInfo,
		$saveMessageKey
	) {
		$this->fallbackChain = $fallbackChain;
		$this->language = $language;
		$this->entityInfo = $entityInfo;
		$this->saveMessageKey = $saveMessageKey;
	}

	public function newLexemeView() {
		$templates = include __DIR__ . '/../../../resources/templates.php';
		$templateFactory = new LexemeTemplateFactory( $templates );

		$languageDirectionalityLookup = new MediaWikiLanguageDirectionalityLookup();
		$localizedTextProvider = new MediaWikiLocalizedTextProvider( $this->language );
		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			new EntityInfoTermLookup( $this->entityInfo ),
			$this->fallbackChain
		);

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$editSectionGenerator = $this->newToolbarEditSectionGenerator();

		$languageNameLookup = WikibaseLexemeServices::getLanguageNameLookup();

		$statementSectionsView = $wikibaseRepo->getViewFactory()->newStatementSectionsView(
			$this->language->getCode(),
			$labelDescriptionLookup,
			$this->fallbackChain,
			$editSectionGenerator
		);

		$statementGroupListView = $wikibaseRepo->getViewFactory()->newStatementGroupListView(
			$this->language->getCode(),
			$labelDescriptionLookup,
			$this->fallbackChain,
			$editSectionGenerator
		);

		$idLinkFormatter = $wikibaseRepo->getEntityIdHtmlLinkFormatterFactory()
			->getEntityIdFormatter( $this->language );

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
			$languageNameLookup
		);

		return new LexemeView(
			TemplateFactory::getDefaultInstance(),
			$languageDirectionalityLookup,
			$this->language->getCode(),
			$formsView,
			$sensesView,
			$statementSectionsView,
			new LexemeTermFormatter(
				$localizedTextProvider
					->get( 'wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma' )
			),
			$idLinkFormatter,
			$this->saveMessageKey
		);
	}

	private function newToolbarEditSectionGenerator() {
		return new ToolbarEditSectionGenerator(
			new RepoSpecialPageLinker(),
			TemplateFactory::getDefaultInstance(),
			new MediaWikiLocalizedTextProvider( $this->language )
		);
	}

}

<?php

namespace Wikibase\Lexeme\Presentation\View;

use DerivativeContext;
use Language;
use RequestContext;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\TermLanguageFallbackChain;
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
	 * @var TermLanguageFallbackChain
	 */
	private $termFallbackChain;

	/**
	 * @var Language
	 */
	private $language;

	public function __construct(
		Language $language,
		TermLanguageFallbackChain $termFallbackChain
	) {
		$this->termFallbackChain = $termFallbackChain;
		$this->language = $language;
	}

	public function newLexemeView() {
		$templates = include __DIR__ . '/../../../resources/templates.php';
		$templateFactory = new LexemeTemplateFactory( $templates );

		$languageDirectionalityLookup = WikibaseRepo::getLanguageDirectionalityLookup();
		$localizedTextProvider = new MediaWikiLocalizedTextProvider( $this->language );

		$viewFactory = WikibaseRepo::getViewFactory();

		$editSectionGenerator = $this->newToolbarEditSectionGenerator();

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setLanguage( $this->language );
		$languageNameLookup = WikibaseLexemeServices::getLanguageNameLookupFactory()
			->getForContextSource( $context );

		$statementSectionsView = $viewFactory->newStatementSectionsView(
			$this->language->getCode(),
			$this->termFallbackChain,
			$editSectionGenerator
		);

		$statementGroupListView = $viewFactory->newStatementGroupListView(
			$this->language->getCode(),
			$this->termFallbackChain,
			$editSectionGenerator
		);

		$idLinkFormatter = WikibaseRepo::getEntityIdHtmlLinkFormatterFactory()
			->getEntityIdFormatter( $this->language );

		$formsView = new FormsView(
			$localizedTextProvider,
			$templateFactory,
			$idLinkFormatter,
			$statementGroupListView,
			WikibaseLexemeServices::getGrammaticalFeaturesOrderProvider()
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
			$idLinkFormatter
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

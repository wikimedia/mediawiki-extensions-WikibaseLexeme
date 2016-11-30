<?php

namespace Wikibase\Lexeme\View;

use InvalidArgumentException;
use Language;
use Message;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\View\EntityTermsView;
use Wikibase\View\EntityView;
use Wikibase\View\HtmlTermRenderer;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikimedia\Assert\Assert;

/**
 * Class for creating HTML views for Lexeme instances.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeView extends EntityView {

	/**
	 * @var StatementSectionsView
	 */
	private $statementSectionsView;

	/**
	 * @var TemplateFactory
	 */
	protected $templateFactory;

	/**
	 * @var string
	 */
	protected $languageCode;

	/**
	 * @var HtmlTermRenderer
	 */
	private $htmlTermRenderer;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EntityTermsView $entityTermsView
	 * @param StatementSectionsView $statementSectionsView
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param HtmlTermRenderer $htmlTermRenderer
	 * @param string $languageCode
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		StatementSectionsView $statementSectionsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		HtmlTermRenderer $htmlTermRenderer,
		$languageCode
	) {
		parent::__construct(
			$templateFactory,
			$entityTermsView,
			$languageDirectionalityLookup,
			$languageCode
		);

		$this->statementSectionsView = $statementSectionsView;
		$this->templateFactory = $templateFactory;
		$this->languageCode = $languageCode;
		$this->htmlTermRenderer = $htmlTermRenderer;
	}

	/**
	 * @see EntityView::getMainHtml
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	protected function getMainHtml( EntityDocument $entity ) {
		/** @var Lexeme $entity */
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		$html = $this->getHtmlForLexicalCategoryAndLanguage( $entity )
			. $this->templateFactory->render( 'wikibase-toc' )
			. $this->statementSectionsView->getHtml( $entity->getStatements() );

		return $html;
	}

	/**
	 * @see EntityView::getSideHtml
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	protected function getSideHtml( EntityDocument $entity ) {
		return '';
	}

	/**
	 * @param EntityDocument $entity
	 * @return string
	 */
	public function getTitleHtml( EntityDocument $entity ) {
		/** @var Lexeme $entity */
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		$isEmpty = true;
		$idInParenthesesHtml = '';
		$labelHtml = '';

		if ( $entity->getId() !== null ) {
			$id = $entity->getId()->getSerialization();
			$idInParenthesesHtml = htmlspecialchars(
				$this->getLocalizedMessage( 'parentheses', [ $id ] )
			);

			$label = $this->getMainTerm( $entity->getLemmata() );
			if ( $label !== null ) {
				$labelHtml = $this->htmlTermRenderer->renderTerm( $label );
				$isEmpty = false;
			}
		}
		$title = $isEmpty ? htmlspecialchars(
			$this->getLocalizedMessage( 'wikibase-label-empty' ) ) : $labelHtml;
		return $this->templateFactory->render(
			'wikibase-title',
			$isEmpty ? 'wb-empty' : '',
			$title,
			$idInParenthesesHtml
		);
	}

	private function getHtmlForLexicalCategoryAndLanguage( $entity ) {
		// TODO: Implement when building LexicalCategory and Language
		return '';
	}

	/**
	 * @param TermList|null $lemmata
	 * @return Term|null
	 */
	private function getMainTerm( $lemmata ) {

		if ( is_null( $lemmata ) || $lemmata->isEmpty() ) {
			return null;
		}

		// Return the first term, until we build a proper UI
		foreach ( $lemmata->getIterator() as $term ) {
			return $term;
		}

		return null;
	}

	private function getLocalizedMessage( $key, array $params = [] ) {
		return ( new Message( $key, $params, Language::factory( $this->languageCode ) ) )->text();
	}

}

<?php

namespace Wikibase\Lexeme\View;

use InvalidArgumentException;
use Language;
use Message;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
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
	 * @var HtmlTermRenderer
	 */
	private $htmlTermRenderer;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EntityTermsView $entityTermsView
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param string $languageCode
	 * @param StatementSectionsView $statementSectionsView
	 * @param HtmlTermRenderer $htmlTermRenderer
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		$languageCode,
		StatementSectionsView $statementSectionsView,
		HtmlTermRenderer $htmlTermRenderer,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		parent::__construct(
			$templateFactory,
			$entityTermsView,
			$languageDirectionalityLookup,
			$languageCode
		);

		$this->statementSectionsView = $statementSectionsView;
		$this->htmlTermRenderer = $htmlTermRenderer;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * @see EntityView::getMainHtml
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException if the entity type does not match.
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
	 *
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

			$label = $this->getMainTerm( $entity->getLemmas() );
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

	/**
	 * @param Lexeme $lexeme
	 *
	 * @return string HTML
	 */
	private function getHtmlForLexicalCategoryAndLanguage( Lexeme $lexeme ) {
		$lexicalCategory = $this->getItemIdHtml( $lexeme->getLexicalCategory() );
		$language = $this->getItemIdHtml( $lexeme->getLanguage() );

		switch ( true ) {
			case $language === null && $lexicalCategory === null:
				return '';
			case $lexicalCategory === null:
				return $this->getLocalizedMessage(
					'wikibase-lexeme-view-language',
					[ $language ]
				);
			case $language === null:
				return $this->getLocalizedMessage(
					'wikibase-lexeme-view-lexical-category',
					[ $lexicalCategory ]
				);
			default:
				return $this->getLocalizedMessage(
					'wikibase-lexeme-view-language-lexical-category',
					[ $lexicalCategory, $language ]
				);
		}
	}

	/**
	 * @param TermList|null $lemmas
	 *
	 * @return Term|null
	 */
	private function getMainTerm( TermList $lemmas = null ) {
		if ( $lemmas === null || $lemmas->isEmpty() ) {
			return null;
		}

		// Return the first term, until we build a proper UI
		foreach ( $lemmas->getIterator() as $term ) {
			return $term;
		}

		return null;
	}

	/**
	 * @param string $key
	 * @param array $params
	 *
	 * @return string Plain text
	 */
	private function getLocalizedMessage( $key, array $params = [] ) {
		return ( new Message( $key, $params, Language::factory( $this->languageCode ) ) )->text();
	}

	/**
	 * @param ItemId|null $itemId
	 *
	 * @return string|null
	 */
	private function getItemIdHtml( ItemId $itemId = null ) {
		if ( $itemId === null ) {
			return null;
		}

		try {
			$label = $this->labelDescriptionLookup->getLabel( $itemId );
		} catch ( LabelDescriptionLookupException $e ) {
			return $itemId->getSerialization();
		}

		if ( $label === null ) {
			return $itemId->getSerialization();
		}

		return $this->htmlTermRenderer->renderTerm( $label );
	}

}

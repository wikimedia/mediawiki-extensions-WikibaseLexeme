<?php

namespace Wikibase\Lexeme\View;

use DataValues\StringValue;
use InvalidArgumentException;
use Language;
use Message;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeForm;
use Wikibase\Lexeme\DataModel\LexemeFormId;
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
	 * @var LexemeFormsView
	 */
	private $formsView;

	/**
	 * @var SensesView
	 */
	private $sensesView;

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
	 * @param LexemeFormsView $formsView
	 * @param SensesView $sensesView
	 * @param StatementSectionsView $statementSectionsView
	 * @param HtmlTermRenderer $htmlTermRenderer
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		$languageCode,
		LexemeFormsView $formsView,
		SensesView $sensesView,
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

		$this->formsView = $formsView;
		$this->sensesView = $sensesView;
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

		// TODO: This obviously is a dummy that must be removed
		$grammaticalFeatures1 = [ new ItemId( 'Q2' ) ];
		$grammaticalFeatures2 = [ new ItemId( 'Q2' ), new ItemId( 'Q3' ) ];
		$statements1 = new StatementList( [
			new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) )
		] );
		$statements2 = new StatementList( [
			new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) ),
			new Statement( new PropertyValueSnak(
				new PropertyId( 'P3' ),
				new StringValue( 'asd' )
			) ),
		 ] );

		$forms = [
			new LexemeForm( new LexemeFormId( 'F1' ), 'A', [] ),
			new LexemeForm( new LexemeFormId( 'F2' ), 'B', $grammaticalFeatures1, $statements1 ),
			new LexemeForm( new LexemeFormId( 'F3' ), 'C', $grammaticalFeatures2, $statements2 ),
		];

		$html = $this->getHtmlForLexicalCategoryAndLanguage( $entity )
			. $this->templateFactory->render( 'wikibase-toc' )
			. $this->statementSectionsView->getHtml( $entity->getStatements() )
			. $this->formsView->getHtml( $forms )
			. $this->sensesView->getHtml();

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

		return $this->getLocalizedMessage(
			'wikibase-lexeme-view-language-lexical-category',
			[ $lexicalCategory, $language ]
		);
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
	 * @param ItemId $itemId
	 *
	 * @return string HTML
	 */
	private function getItemIdHtml( ItemId $itemId ) {
		try {
			$label = $this->labelDescriptionLookup->getLabel( $itemId );
		} catch ( LabelDescriptionLookupException $e ) {
			$label = null;
		}

		if ( $label === null ) {
			return $itemId->getSerialization();
		}

		return $this->htmlTermRenderer->renderTerm( $label );
	}

}

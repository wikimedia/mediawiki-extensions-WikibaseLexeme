<?php

namespace Wikibase\Lexeme\View;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\StatementSectionsView;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormsView {

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var LexemeTemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var EntityIdHtmlLinkFormatter
	 */
	private $entityIdHtmlFormatter;

	/**
	 * @var StatementSectionsView
	 */
	private $statementSectionView;

	public function __construct(
		LocalizedTextProvider $textProvider,
		LexemeTemplateFactory $templateFactory,
		EntityIdHtmlLinkFormatter $entityIdHtmlFormatter,
		StatementSectionsView $statementSectionView
	) {
		$this->textProvider = $textProvider;
		$this->templateFactory = $templateFactory;
		$this->entityIdHtmlFormatter = $entityIdHtmlFormatter;
		$this->statementSectionView = $statementSectionView;
	}

	/**
	 * @param Form[] $forms
	 *
	 * @return string HTML
	 */
	public function getHtml( array $forms ) {
		$html = '<div class="wikibase-lexeme-forms-section">';
		$html .= '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="forms">'
			. htmlspecialchars( $this->textProvider->get( 'wikibase-lexeme-view-forms' ) )
			. '</span>'
			. '</h2>';

		$html .= '<div class="wikibase-lexeme-forms ">';
		foreach ( $forms as $form ) {
			$html .= $this->getFormHtml( $form );
		}
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * @param Form $form
	 *
	 * @return string HTML
	 */
	private function getFormHtml( Form $form ) {
		//TODO Change to rendering all the representations
		$representation = $form->getRepresentations()->getIterator()->current()->getText();

		$grammaticalFeaturesHtml = $this->templateFactory->render(
			'wikibase-lexeme-form-grammatical-features',
			[ implode(
				$this->textProvider->get( 'comma-separator' ),
				array_map(
					function ( ItemId $id ) {
						return $this->getGrammaticalFeatureHtml( $id );
					},
					$form->getGrammaticalFeatures()
				)
			) ]
		);

		return $this->templateFactory->render( 'wikibase-lexeme-form', [
			'some language',
			htmlspecialchars( $representation ),
			wfMessage( 'parentheses' )->rawParams( htmlspecialchars( $form->getId()->getSerialization() ) )
				->text(),
			$grammaticalFeaturesHtml,
			$this->statementSectionView->getHtml( $form->getStatements() )
		] );
	}

	/**
	 * @param ItemId $id
	 * @return string
	 */
	private function getGrammaticalFeatureHtml( ItemId $id ) {
		return $this->entityIdHtmlFormatter->formatEntityId( $id );
	}

}

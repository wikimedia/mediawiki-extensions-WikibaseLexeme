<?php

namespace Wikibase\Lexeme\View;

use Wikibase\Lexeme\DataModel\LexemeForm;
use Wikibase\Lexeme\DataModel\LexemeFormId;
use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormsView {

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	public function __construct( LocalizedTextProvider $textProvider ) {
		$this->textProvider = $textProvider;
	}

	/**
	 * @param LexemeForm[] $forms
	 *
	 * @return string HTML
	 */
	public function getHtml( array $forms ) {
		$html = '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="forms">'
			. htmlspecialchars( $this->textProvider->get( 'wikibase-lexeme-view-forms' ) )
			. '</span>'
			. '</h2>';

		$html .= '<div class="wikibase-lexeme-forms">';
		foreach ( $forms as $form ) {
			$html .= $this->getFormHtml( $form );
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * @param LexemeForm $form
	 *
	 * @return string HTML
	 */
	private function getFormHtml( LexemeForm $form ) {
		$representation = $form->getRepresentation();

		return '<h3 class="wikibase-lexeme-form-representation">'
			. htmlspecialchars( $representation )
			. $this->getFormIdHtml( $form->getId() )
			. '</h3>';
	}

	/**
	 * @param LexemeFormId|null $id
	 *
	 * @return string HTML
	 */
	private function getFormIdHtml( LexemeFormId $id = null ) {
		if ( $id === null ) {
			return '';
		}

		return ' <span class="wikibase-title-id">'
			. wfMessage( 'parentheses' )->rawParams( htmlspecialchars( $id->getSerialization() ) )
				->text()
			. '</span>';
	}

}

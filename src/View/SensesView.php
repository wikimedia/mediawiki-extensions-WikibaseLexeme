<?php

namespace Wikibase\Lexeme\View;

use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0+
 */
class SensesView {

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	public function __construct( LocalizedTextProvider $textProvider ) {
		$this->textProvider = $textProvider;
	}

	/**
	 * @return string HTML
	 */
	public function getHtml() {
		$html = '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="senses">'
			. htmlspecialchars( $this->textProvider->get( 'wikibase-lexeme-view-senses' ) )
			. '</span>'
			. '</h2>';

		$html .= '<div class="wikibase-lexeme-senses">';
		$html .= '</div>';

		return $html;
	}

}

<?php

namespace Wikibase\Lexeme\View;

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
	 * @return string HTML
	 */
	public function getHtml() {
		return '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="forms">'
			. htmlspecialchars( $this->textProvider->get( 'wikibase-lexeme-view-forms' ) )
			. '</span>'
			. '</h2>';
	}

}

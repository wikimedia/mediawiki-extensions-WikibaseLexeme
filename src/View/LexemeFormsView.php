<?php

namespace Wikibase\Lexeme\View;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormsView {

	/**
	 * @return string HTML
	 */
	public function getHtml() {
		// FIXME: Next step should be to localize the string "Forms".
		return '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="forms">Forms</span>'
			. '</h2>';
	}

}

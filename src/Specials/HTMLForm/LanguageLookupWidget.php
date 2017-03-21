<?php

namespace Wikibase\Lexeme\Specials\HTMLForm;

use OOUI\TextInputWidget;

/**
 * Needed to infuse the LanguageLookupWidget into an existing form field in the frontend
 *
 * @license GPL-2.0+
 */
class LanguageLookupWidget extends TextInputWidget {

	protected function getJavaScriptClassName() {
		return 'wikibase.lexeme.widgets.LanguageLookupWidget';
	}

}

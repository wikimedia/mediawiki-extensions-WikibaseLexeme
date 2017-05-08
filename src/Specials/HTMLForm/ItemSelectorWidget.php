<?php

namespace Wikibase\Lexeme\Specials\HTMLForm;

use OOUI\TextInputWidget;

/**
 * Needed to infuse the ItemSelectorWidget into an existing form field in the frontend
 *
 * TODO: make it configurable from PHP in order to inject API URL, timeout etc.
 *
 * @license GPL-2.0+
 */
class ItemSelectorWidget extends TextInputWidget {

	protected function getJavaScriptClassName() {
		return 'wikibase.lexeme.widgets.ItemSelectorWidget';
	}

}

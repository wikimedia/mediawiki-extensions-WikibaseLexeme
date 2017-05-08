<?php

namespace Wikibase\Lexeme\Specials\HTMLForm;

use Wikibase\Repo\Specials\HTMLForm\HTMLItemReferenceField;

/**
 * Passes ItemSelectorWidget instead of OOUI\TextInputWidget to the frontend
 *
 * @license GPL-2.0+
 */
class ItemSelectorWidgetField extends HTMLItemReferenceField {

	protected function getInputWidget( $params ) {
		return new ItemSelectorWidget( $params );
	}

}

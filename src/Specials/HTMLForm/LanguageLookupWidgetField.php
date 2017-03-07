<?php

namespace Wikibase\Lexeme\Specials\HTMLForm;

use Wikibase\Repo\Specials\HTMLForm\HTMLItemReferenceField;

/**
 * Passes LanguageLookupWidget instead of OOUI\TextInputWidget to the frontend
 *
 * @license GPL-2.0+
 */
class LanguageLookupWidgetField extends HTMLItemReferenceField {

	protected function getInputWidget( $params ) {
		return new LanguageLookupWidget( $params );
	}

}

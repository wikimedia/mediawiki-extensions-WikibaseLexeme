'use strict';

const Page = require( '../../../../../tests/selenium/pageobjects/page' ); // TODO: Love it!

class SpecialVersionPage extends Page {
	get wikibaseLexemeExtensionLink() {
		return browser.element( '#mw-version-ext-wikibase-WikibaseLexeme' );
	}

	open() {
		super.open( 'Special:Version' );
	}
}

module.exports = new SpecialVersionPage();

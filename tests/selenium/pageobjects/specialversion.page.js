'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class SpecialVersionPage extends Page {
	get wikibaseLexemeExtensionLink() {
		return $( '#mw-version-ext-wikibase-WikibaseLexeme' );
	}

	open() {
		super.openTitle( 'Special:Version' );
	}
}

module.exports = new SpecialVersionPage();

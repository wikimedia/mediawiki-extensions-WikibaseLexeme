'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class RecentChangesPage extends Page {
	get lastLexeme() { return $( '[data-target-page^="Lexeme:L"]' ); }

	open() {
		super.openTitle( 'Special:RecentChanges' );
	}

}

module.exports = new RecentChangesPage();

'use strict';

const Page = require( '../../../../../tests/selenium/pageobjects/page' );

class HistoryPage extends Page {

	get revisions() { return $( 'ul#pagehistory' ).$$( 'li' ); }

	open( id ) {
		super.open( 'Lexeme:' + id + '&action=history' );
	}

	undoFirstRevision() {
		this.revisions[ 0 ].$( '.mw-history-undo a' ).click();
	}

}

module.exports = new HistoryPage();

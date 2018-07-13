'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class HistoryPage extends Page {

	get revisions() { return $( 'ul#pagehistory' ).$$( 'li' ); }

	open( id ) {
		super.openTitle( 'Lexeme:' + id, { action: 'history' } );
	}

	undoFirstRevision() {
		this.revisions[ 0 ].$( '.mw-history-undo a' ).click();
	}

}

module.exports = new HistoryPage();

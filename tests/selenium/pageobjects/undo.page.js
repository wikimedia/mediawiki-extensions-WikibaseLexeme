'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class UndoPage extends Page {

	get undoButton() { return browser.element( 'form#undo button' ); }

	save() {
		this.undoButton.click();
	}

}

module.exports = new UndoPage();

'use strict';

const Page = require( '../../../../../tests/selenium/pageobjects/page' );

class UndoPage extends Page {

	get undoButton() { return browser.element( 'form#undo button' ); }

	save() {
		this.undoButton.click();
	}

}

module.exports = new UndoPage();

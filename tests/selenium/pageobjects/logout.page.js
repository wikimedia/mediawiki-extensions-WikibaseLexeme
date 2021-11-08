'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class LogoutPage extends Page {
	get logoutButton() {
		return $( '#mw-content-text button' );
	}

	open() {
		super.openTitle( 'Special:UserLogout' );
	}

	ensureLoggedOut() {
		this.open();
		if ( !this.logoutButton.isExisting() ) {
			return;
		}
		this.logoutButton.click();
		this.logoutButton.waitForExist( { reverse: true } );
	}

}

module.exports = new LogoutPage();

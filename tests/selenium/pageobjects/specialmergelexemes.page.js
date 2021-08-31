'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class SpecialMergeLexemesPage extends Page {
	static get MERGE_LEXEME_SELECTORS() {
		return {
			FROMID: '#wb-mergelexemes-from-id',
			TOID: '#wb-mergelexemes-to-id',
			SUBMIT_BUTTON: '#wb-mergelexemes-submit'
		};
	}

	isUserBlockedErrorVisible() {
		$( '#mw-returnto' ).waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		return ( $( '#firstHeading' ).getText() === 'User is blocked' );
	}

	showsForm() {
		const $fromId = $( this.constructor.MERGE_LEXEME_SELECTORS.FROMID ),
			$toId = $( this.constructor.MERGE_LEXEME_SELECTORS.TOID ),
			$submit = $( this.constructor.MERGE_LEXEME_SELECTORS.SUBMIT_BUTTON );
		return $fromId.isDisplayed() &&
			$toId.isDisplayed() &&
			$submit.isDisplayed();
	}

	open() {
		super.openTitle( 'Special:MergeLexemes' );
	}
}

module.exports = new SpecialMergeLexemesPage();

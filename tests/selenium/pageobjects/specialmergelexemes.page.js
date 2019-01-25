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
		$( '#mw-returnto' ).waitForVisible();
		return ( $( '#firstHeading' ).getText() === 'User is blocked' );
	}

	showsForm() {
		let $fromId = $( this.constructor.MERGE_LEXEME_SELECTORS.FROMID ),
			$toId = $( this.constructor.MERGE_LEXEME_SELECTORS.TOID ),
			$submit = $( this.constructor.MERGE_LEXEME_SELECTORS.SUBMIT_BUTTON );
		return $fromId.isVisible() &&
		$toId.isVisible() &&
		$submit.isVisible();
	}

	open() {
		super.openTitle( 'Special:MergeLexemes' );
	}
}

module.exports = new SpecialMergeLexemesPage();

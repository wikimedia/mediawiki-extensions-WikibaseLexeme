'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class NonExistingLexemePage extends Page {

	static get NON_EXISTING_LEXEME_PAGE_SELECTORS() {
		return {
			FIRST_HEADING: '#firstHeading',
			NO_ARTICLE_TEXT: '.noarticletext'
		};
	}

	get firstHeading() {
		return $( this.constructor.NON_EXISTING_LEXEME_PAGE_SELECTORS.FIRST_HEADING );
	}

	get noArticleText() {
		return $( this.constructor.NON_EXISTING_LEXEME_PAGE_SELECTORS.NO_ARTICLE_TEXT );
	}

	/**
	 * Open a page for a non-existing lexeme
	 */
	open() {
		super.openTitle( 'Lexeme:L-invalid' );
		this.firstHeading.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
	}

}

module.exports = new NonExistingLexemePage();

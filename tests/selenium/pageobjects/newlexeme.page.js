'use strict';

const Page = require( '../../../../../tests/selenium/pageobjects/page' );

class NewLexemePage extends Page {

	static get NEW_LEXEME_SELECTORS() {
		return {
			LEMMA: '#wb-newlexeme-lemma',
			LANGUAGE: '#wb-newlexeme-lexeme-language',
			LEXICAL_CATEGORY: '#wb-newlexeme-lexicalCategory'
		};
	}

	open() {
		super.open( 'Special:NewLexeme' );
	}

	showsForm() {
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LEMMA );
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE );
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY );

		return true;
	}
}

module.exports = new NewLexemePage();

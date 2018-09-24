'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	MixinBuilder = require( '../../../../Wikibase/repo/tests/selenium/pagesections/mixinbuilder' ),
	ComponentInteraction = require( '../../../../Wikibase/repo/tests/selenium/pagesections/ComponentInteraction' );

class NewLexemePage extends MixinBuilder.mix( Page ).with( ComponentInteraction ) {

	static get NEW_LEXEME_SELECTORS() {
		return {
			LEMMA: '#wb-newlexeme-lemma',
			LANGUAGE: '#wb-newlexeme-lexeme-language',
			LEXICAL_CATEGORY: '#wb-newlexeme-lexicalCategory',
			LEMMA_LANGUAGE: '#wb-newlexeme-lemma-language',
			SUBMIT_BUTTON: '#wb-newentity-submit button'
		};
	}

	open() {
		super.openTitle( 'Special:NewLexeme' );
	}

	showsForm() {
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LEMMA );
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE );
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY );

		return true;
	}

	createLexeme( lemma, language, lexicalCategory, lemmaLanguage ) {
		this.setLemma( lemma );

		this.setLexemeLanguage( language );
		this.setLexicalCategory( lexicalCategory );

		if ( typeof lemmaLanguage !== 'undefined' ) {
			browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ).waitForVisible();
			this.setLemmaLanguage( lemmaLanguage );
		} else {
			// ensure lemma language input is not presented (logic is asynchronous)
			browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ).waitForVisible( 1000, true );
		}

		this.clickSubmit();
	}

	setLemma( lemma ) {
		browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).setValue( lemma );
	}

	setLexemeLanguage( language ) {
		this.setValueOnLookupElement(
			browser.$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ),
			language
		);
	}

	setLexicalCategory( lexicalCategory ) {
		this.setValueOnLookupElement(
			browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ),
			lexicalCategory
		);
	}

	setLemmaLanguage( lemmaLanguage ) {
		this.setValueOnComboboxElement(
			browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ),
			lemmaLanguage
		);
	}

	clickSubmit() {
		browser.$( this.constructor.NEW_LEXEME_SELECTORS.SUBMIT_BUTTON ).click();
	}

	showsLemmaLanguageField() {
		browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ).waitForVisible();
		return true;
	}

}

module.exports = new NewLexemePage();

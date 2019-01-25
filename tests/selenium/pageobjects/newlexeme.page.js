'use strict';

const Page = require( 'wdio-mediawiki/Page' );

let MixinBuilder, ComponentInteraction;
try {
	MixinBuilder = require( 'wdio-wikibase/pagesections/mixinbuilder' );
	ComponentInteraction = require( 'wdio-wikibase/pagesections/ComponentInteraction' );
} catch ( e ) {
	MixinBuilder = require( '../../../../Wikibase/repo/tests/selenium/wdio-wikibase/pagesections/mixinbuilder' );
	ComponentInteraction = require( '../../../../Wikibase/repo/tests/selenium/wdio-wikibase/pagesections/ComponentInteraction' );
}

class NewLexemePage extends MixinBuilder.mix( Page ).with( ComponentInteraction ) {

	static get NEW_LEXEME_SELECTORS() {
		return {
			LEMMA: '#wb-newlexeme-lemma',
			LANGUAGE: '#wb-newlexeme-lexeme-language',
			LANGUAGE_SELECTOR_VALUE: '#wb-newlexeme-lexeme-language input.oo-ui-wikibase-item-selector-value',
			LEXICAL_CATEGORY: '#wb-newlexeme-lexicalCategory',
			LEXICAL_CATEGORY_SELECTOR_VALUE: '#wb-newlexeme-lexicalCategory input.oo-ui-wikibase-item-selector-value',
			LEMMA_LANGUAGE: '#wb-newlexeme-lemma-language',

			SUBMIT_BUTTON: '#wb-newentity-submit button'
		};
	}

	open( query ) {
		super.openTitle( 'Special:NewLexeme', query );
	}

	/*
	* Waits to see if eventually the form is shown
	*/
	showsForm() {
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LEMMA );
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE );
		browser.waitForVisible( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY );

		return true;
	}

	/*
	* Checks if any elements of the form are currently visible
	*/
	formCurrentlyVisible() {
		return $( this.constructor.NEW_LEXEME_SELECTORS.LEMMA ).isVisible() ||
			$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ).isVisible() ||
			$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ).isVisible() ||
			$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ).isVisible();

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

	getLemma() {
		return browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).getValue();
	}

	setLexemeLanguage( language ) {
		this.setValueOnLookupElement(
			browser.$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ),
			language
		);
	}

	getLexemeLanguage() {
		return browser.$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE_SELECTOR_VALUE ).getValue();
	}

	setLexicalCategory( lexicalCategory ) {
		this.setValueOnLookupElement(
			browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ),
			lexicalCategory
		);
	}

	getLexicalCategory() {
		return browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY_SELECTOR_VALUE ).getValue();
	}

	setLemmaLanguage( lemmaLanguage ) {
		this.setValueOnComboboxElement(
			browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ),
			lemmaLanguage
		);
	}

	getLemmaLanguage() {
		return browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE + ' input' ).getValue();
	}

	clickSubmit() {
		browser.$( this.constructor.NEW_LEXEME_SELECTORS.SUBMIT_BUTTON ).click();
	}

	showsLemmaLanguageField() {
		browser.$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ).waitForVisible();
		return true;
	}

	isUserBlockedErrorVisible() {
		$( '#mw-returnto' ).waitForVisible();
		return ( $( '#firstHeading' ).getText() === 'User is blocked' );
	}

}

module.exports = new NewLexemePage();

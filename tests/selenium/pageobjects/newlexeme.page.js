'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	MixinBuilder = require( 'wdio-wikibase/pagesections/mixinbuilder' ),
	ComponentInteraction = require( 'wdio-wikibase/pagesections/ComponentInteraction' );

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
		$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA ).waitForDisplayed();
		$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ).waitForDisplayed();
		$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ).waitForDisplayed();

		return true;
	}

	/*
	* Checks if any elements of the form are currently visible
	*/
	formCurrentlyVisible() {
		return $( this.constructor.NEW_LEXEME_SELECTORS.LEMMA ).isDisplayed() ||
			$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ).isDisplayed() ||
			$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ).isDisplayed() ||
			$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ).isDisplayed();

	}

	createLexeme( lemma, language, lexicalCategory, lemmaLanguage ) {
		this.setLemma( lemma );

		this.setLexemeLanguage( language );
		this.setLexicalCategory( lexicalCategory );

		if ( typeof lemmaLanguage !== 'undefined' ) {
			$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ).waitForDisplayed();
			this.setLemmaLanguage( lemmaLanguage );
		} else {
			// ensure lemma language input is not presented (logic is asynchronous)
			$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE )
				.waitForDisplayed( { timeout: 1000, reverse: true } );
		}

		this.clickSubmit();
	}

	setLemma( lemma ) {
		$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).setValue( lemma );
	}

	getLemma() {
		return $( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).getValue();
	}

	setLexemeLanguage( language ) {
		this.setValueOnLookupElement(
			$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ),
			language
		);
	}

	getLexemeLanguage() {
		return $( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE_SELECTOR_VALUE ).getValue();
	}

	setLexicalCategory( lexicalCategory ) {
		this.setValueOnLookupElement(
			$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ),
			lexicalCategory
		);
	}

	getLexicalCategory() {
		return $( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY_SELECTOR_VALUE ).getValue();
	}

	setLemmaLanguage( lemmaLanguage ) {
		this.setValueOnComboboxElement(
			$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ),
			lemmaLanguage
		);
	}

	// FIXME this method is a modified copy of the one from wdio-wikibase. The change should be upstreamed!
	setValueOnComboboxElement( element, value ) {
		element.$( 'input' ).setValue( value );
		$( `${this.constructor.OOUI_SELECTORS.OVERLAY} ${this.constructor.OOUI_SELECTORS.OPTION_WIDGET_SELECTED}` )
			.waitForExist();
		// close suggestion overlay
		element.$( this.constructor.OOUI_SELECTORS.COMBOBOX_DROPDOWN ).click();
	}

	getLemmaLanguage() {
		return $( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE + ' input' ).getValue();
	}

	clickSubmit() {
		$( this.constructor.NEW_LEXEME_SELECTORS.SUBMIT_BUTTON ).click();
	}

	showsLemmaLanguageField() {
		$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA_LANGUAGE ).waitForDisplayed();
		return true;
	}

	isUserBlockedErrorVisible() {
		$( '#mw-returnto' ).waitForDisplayed();
		return ( $( '#firstHeading' ).getText() === 'User is blocked' );
	}

}

module.exports = new NewLexemePage();

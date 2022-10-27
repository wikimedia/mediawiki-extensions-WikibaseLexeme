'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	MixinBuilder = require( 'wdio-wikibase/pagesections/mixinbuilder' ),
	ComponentInteraction = require( 'wdio-wikibase/pagesections/ComponentInteraction' );

class NewLexemePage extends MixinBuilder.mix( Page ).with( ComponentInteraction ) {

	static get NEW_LEXEME_SELECTORS() {
		return {
			LEMMA: '.wbl-snl-lemma-input',
			LANGUAGE: '.wbl-snl-language-lookup',
			SPELLING_VARIANT: '.wbl-snl-spelling-variant-lookup',
			LEXICAL_CATEGORY: '.wbl-snl-lexical-category-lookup',

			SUBMIT_BUTTON: '.wbl-snl-form button[type=submit]'
		};
	}

	open( query ) {
		super.openTitle( 'Special:NewLexeme', query );
	}

	/*
	* Waits to see if eventually the form is shown
	*/
	showsForm() {
		$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA ).waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ).waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ).waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

		return true;
	}

	createLexeme( lemma, language, lexicalCategory, languageVariant ) {
		this.setLemma( lemma );

		this.setLexemeLanguage( language );
		this.setSpellingVariant( languageVariant );
		this.setLexicalCategory( lexicalCategory );

		this.clickSubmit();
	}

	setLemma( lemma ) {
		$( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).setValue( lemma );
	}

	setLexemeLanguage( language ) {
		this.setValueOnWikitLookup(
			$( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ),
			language
		);
	}

	setLexicalCategory( lexicalCategory ) {
		this.setValueOnWikitLookup(
			$( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ),
			lexicalCategory
		);
	}

	setSpellingVariant( languageVariant ) {
		this.setValueOnWikitLookup(
			$( this.constructor.NEW_LEXEME_SELECTORS.SPELLING_VARIANT ),
			languageVariant
		);
	}

	setValueOnWikitLookup( element, value ) {
		element.$( 'input' ).setValue( value );
		const option = element.$( '.wikit-LookupInput__menu .wikit-OptionsMenu__item' );
		option.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		option.click();
	}

	clickSubmit() {
		$( this.constructor.NEW_LEXEME_SELECTORS.SUBMIT_BUTTON ).click();
	}
}

module.exports = new NewLexemePage();

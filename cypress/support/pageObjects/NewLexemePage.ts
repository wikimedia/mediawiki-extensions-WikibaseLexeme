export class NewLexemePage {

	static NEW_LEXEME_SELECTORS = {
		LEMMA: '.wbl-snl-lemma-input',
		LANGUAGE: '.wbl-snl-language-lookup',
		SPELLING_VARIANT: '.wbl-snl-spelling-variant-lookup',
		LEXICAL_CATEGORY: '.wbl-snl-lexical-category-lookup',

		SUBMIT_BUTTON: '.wbl-snl-form button[type=submit]'
	}

	static WIKIT_LOOKUP_SELECTORS = {
		SELECTED_ELEMENT: '.wikit-LookupInput__menu .wikit-OptionsMenu__item'
	}

	open() {
		cy.visit( 'index.php?title=Special:NewLexeme' )
		return this
	}

	showsForm() {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEMMA );
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE );
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY );
		return this
	}

	createLexeme( lemma, language, lexicalCategory, languageVariant ) {
		this.setLemma( lemma );

		this.setLexemeLanguage( language );
		this.setSpellingVariant( languageVariant );
		this.setLexicalCategory( lexicalCategory );

		this.submit();
		return this;
	}

	setLemma( lemma ) {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).clear().type( lemma );
		return this;
	}

	setLexemeLanguage( language ) {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ).within( () => {
			this._wikitValueLookup( language );
		} )
		return this;
	}

	setLexicalCategory( lexicalCategory ) {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ).within( () => {
			this._wikitValueLookup( lexicalCategory );
		} )
		return this;
	}

	setSpellingVariant( languageVariant ) {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.SPELLING_VARIANT ).within( () => {
			this._wikitValueLookup( languageVariant );
		} )
		return this;
	}

	_wikitValueLookup( value ) {
		cy.get( 'input' ).clear().type( value );
		cy.get( this.constructor.WIKIT_LOOKUP_SELECTORS.SELECTED_ELEMENT ).click();
	}

	submit() {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.SUBMIT_BUTTON ).click();
		return this;
	}
}

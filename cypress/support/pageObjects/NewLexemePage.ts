export class NewLexemePage {

	private static get NEW_LEXEME_SELECTORS(): Record<string, string> {
		return {
			LEMMA: '.wbl-snl-lemma-input',
			LANGUAGE: '.wbl-snl-language-lookup',
			SPELLING_VARIANT: '.wbl-snl-spelling-variant-lookup',
			LEXICAL_CATEGORY: '.wbl-snl-lexical-category-lookup',

			SUBMIT_BUTTON: '.wbl-snl-form button[type=submit]'
		};
	}

	private static get WIKIT_LOOKUP_SELECTORS(): Record<string, string> {
		return {
			SELECTED_ELEMENT: '.wikit-LookupInput__menu .wikit-OptionsMenu__item'
		};
	}

	public open(): this {
		cy.visit( 'index.php?title=Special:NewLexeme' );
		return this;
	}

	public showsForm(): this {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEMMA );
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE );
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY );
		return this;
	}

	public createLexeme(
		lemma: string,
		language: string,
		lexicalCategory: string,
		languageVariant: string
	): this {
		this.setLemma( lemma );

		this.setLexemeLanguage( language );
		this.setSpellingVariant( languageVariant );
		this.setLexicalCategory( lexicalCategory );

		this.submit();
		return this;
	}

	public setLemma( lemma: string ): this {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).clear().type( lemma );
		return this;
	}

	public setLexemeLanguage( language: string ): this {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE ).within( () => {
			this._wikitValueLookup( language );
		} );
		return this;
	}

	public setLexicalCategory( lexicalCategory: string ): this {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY ).within( () => {
			this._wikitValueLookup( lexicalCategory );
		} );
		return this;
	}

	public setSpellingVariant( languageVariant: string ): this {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.SPELLING_VARIANT ).within( () => {
			this._wikitValueLookup( languageVariant );
		} );
		return this;
	}

	private _wikitValueLookup( value: string ): void {
		cy.get( 'input' ).clear().type( value );
		cy.get( this.constructor.WIKIT_LOOKUP_SELECTORS.SELECTED_ELEMENT ).click();
	}

	public submit(): this {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.SUBMIT_BUTTON ).click();
		return this;
	}
}

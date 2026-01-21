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

	public open(): this {
		cy.visit( 'index.php?title=Special:NewLexeme' );
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
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).clear();
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.LEMMA + ' input' ).type( lemma );
		return this;
	}

	public setLexemeLanguage( language: string ): this {
		this._setCodexLookupValue(
			this.constructor.NEW_LEXEME_SELECTORS.LANGUAGE,
			language
		);
		return this;
	}

	public setLexicalCategory( lexicalCategory: string ): this {
		this._setCodexLookupValue(
			this.constructor.NEW_LEXEME_SELECTORS.LEXICAL_CATEGORY,
			lexicalCategory
		);
		return this;
	}

	public setSpellingVariant( languageVariant: string ): this {
		this._setCodexLookupValue(
			this.constructor.NEW_LEXEME_SELECTORS.SPELLING_VARIANT,
			languageVariant
		);
		return this;
	}

	private _setCodexLookupValue( selector: string, value: string ): this {
		cy.get( selector ).find( 'input[aria-controls]' ).clear();
		cy.get( selector ).find( 'input[aria-controls]' ).type( value );
		cy.get( selector ).find( 'input[aria-controls]' ).invoke( 'attr', 'aria-controls' )
			.then( ( id ) => {
				cy.get( `#${ id }` ).find( '.cdx-menu-item--enabled' )
					.click();
			} );
		return this;
	}

	public submit(): this {
		cy.get( this.constructor.NEW_LEXEME_SELECTORS.SUBMIT_BUTTON ).click();
		return this;
	}
}

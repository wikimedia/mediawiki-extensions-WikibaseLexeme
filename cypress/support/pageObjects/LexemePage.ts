import Chainable = Cypress.Chainable;

export class LexemePage {

	private static get LEMMA_WIDGET_SELECTORS(): Record<string, string> {
		return {
			EDIT_BUTTON: '.lemma-widget_edit',
			EDIT_INPUT_LEXEME_LANGUAGE: '#lexeme-language',
			EDIT_INPUT_LEXEME_LEXICAL_CATEGORY: '#lexeme-lexical-category',
			SAVE_BUTTON: '.lemma-widget_save',
			LEMMA_LIST: '.lemma-widget_lemma-list'
		};
	}

	private static get LEMMA_PAGE_SELECTORS(): Record<string, string> {
		return {
			HEADER_ID: '.wb-lexeme-header_id',
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove'
		};
	}

	private static get FORM_WIDGET_SELECTORS(): Record<string, string> {
		return {
			FORM_SECTION_CONTAINER: '.wikibase-lexeme-forms',
			FORM_SECTION_HEADER: '.wikibase-lexeme-forms-section h2#forms',
			FORM_HEADER: '.wikibase-lexeme-form-header',
			FORM_ID: '.wikibase-lexeme-form-id',
			FORM_LIST_ITEM: '.wikibase-lexeme-form',
			GRAMMATICAL_FEATURES: '.wikibase-lexeme-form-grammatical-features-values',
			REPRESENTATION_WIDGET: '.representation-widget',
			REPRESENTATION_LANGUAGE: '.representation-widget_representation-language'
		};
	}

	private static get OOUI_SELECTORS(): Record<string, string> {
		return {
			VISIBLE_ENTITY_SUGGESTION: 'ul.ui-suggester-list li'
		};
	}

	/**
	 * @param formId If provided, get a specific form item. When omitted, gets all.
	 */
	public getFormListItem( formId?: string ): Chainable<JQuery<HTMLElement>> {
		if ( typeof formId === 'undefined' ) {
			return cy.get( this.constructor.FORM_WIDGET_SELECTORS.FORM_LIST_ITEM );
		} else {
			// an example formId: L516-F1
			// the part after the '-' is also the element id for the form's container
			const containerId = '#' + formId.split( '-' )[ 1 ];
			return cy.get( containerId );
		}
	}

	public getFormId( formId?: string ): Chainable<JQuery<HTMLElement>> {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.FORM_ID );
	}

	public getFormEditButton( formId?: string ): Chainable<JQuery<HTMLElement>> {
		return this.getFormListItem( formId )
			.find( this.constructor.LEMMA_PAGE_SELECTORS.EDIT_BUTTON );
	}

	public getFormRemoveButton( formId?: string ): Chainable<JQuery<HTMLElement>> {
		return this.getFormListItem( formId )
			.find( this.constructor.LEMMA_PAGE_SELECTORS.REMOVE_BUTTON );
	}

	public getFormsHeader(): Chainable<JQuery<HTMLElement>> {
		return cy.get( this.constructor.FORM_WIDGET_SELECTORS.FORM_SECTION_HEADER );
	}

	public getFormsContainer(): Chainable<JQuery<HTMLElement>> {
		return cy.get( this.constructor.FORM_WIDGET_SELECTORS.FORM_SECTION_CONTAINER );
	}

	public getGrammaticalFeatureList( formId?: string ): Chainable<JQuery<HTMLElement>> {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES );
	}

	public getRepresentationWidget( formId?: string ): Chainable<JQuery<HTMLElement>> {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_WIDGET );
	}

	public getRepresentationLanguage( formId?: string ): Chainable<JQuery<HTMLElement>> {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_LANGUAGE );
	}

	public getLemmaContainer(): Chainable<JQuery<HTMLElement>> {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.LEMMA_LIST );
	}

	public removeForm( formId: string ): this {
		this.getFormEditButton( formId ).click();
		this.getFormRemoveButton( formId ).click();
		this.getFormListItem( formId ).should( 'not.exist' );
		return this;
	}

	public getHeaderId(): Chainable<string> {
		return cy.get( this.constructor.LEMMA_PAGE_SELECTORS.HEADER_ID )
			.then(
				( element ) => element
					.text()
					.replace( /[^L0-9]/g, '' )
			);
	}

	public open( lexemeId: string ): Chainable {
		const title = 'Lexeme:' + lexemeId;
		return cy.visitTitle( title );
	}

	public startHeaderEditMode(): this {
		cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON ).click();
		this.getLexemeLanguageInput().invoke( 'val' ).should( 'not.be.empty' );
		return this;
	}

	public getLexemeLanguageInput(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LEXEME_LANGUAGE );
	}

	public setLexemeLanguageToItem( item: string ): this {
		this.getLexemeLanguageInput().clear();
		this.getLexemeLanguageInput().type( item );
		return this;
	}

	public getLexemeLexicalCategoryInput(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LEXEME_LEXICAL_CATEGORY );
	}

	public setLexemeLexicalCategoryToItem( item: string ): this {
		this.getLexemeLexicalCategoryInput().clear();
		this.getLexemeLexicalCategoryInput().type( item );
		return this;
	}

	public selectFirstSuggestedEntityOnEntitySelector(): this {
		cy.get( this.constructor.OOUI_SELECTORS.VISIBLE_ENTITY_SUGGESTION )
			.filter( ':visible' ).click();
		return this;
	}

	public headerSaveButton(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.SAVE_BUTTON ).not( ':disabled' );
	}

	public headerSaveButtonNotPresent(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.SAVE_BUTTON ).should( 'not.exist' );
	}

	public setLexemeLanguageItem( item: string ): this {
		this.setLexemeLanguageToItem( item );
		this.selectFirstSuggestedEntityOnEntitySelector();
		this.headerSaveButton().click();
		return this;
	}

	public setLexicalCategoryItem( item: string ): this {
		this.setLexemeLexicalCategoryToItem( item );
		this.selectFirstSuggestedEntityOnEntitySelector();
		this.headerSaveButton().click();
		return this;
	}
}

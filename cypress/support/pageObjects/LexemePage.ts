import Chainable = Cypress.Chainable;

export class LexemePage {

	private static get LEMMA_WIDGET_SELECTORS(): Record<string, string> {
		return { LEMMA_LIST: '.lemma-widget_lemma-list' };
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

	public open( lexemeId: string ): this {
		const title = 'Lexeme:' + lexemeId;
		cy.visitTitle( title );
		return this;
	}
}

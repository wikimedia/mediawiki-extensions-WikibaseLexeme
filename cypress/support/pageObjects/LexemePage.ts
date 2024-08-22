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

	private getFormListItem( formId: string ): Chainable {
		// an example formId: L516-F1
		// the part after the '-' is also the element id for the form's container
		const containerId = '#' + formId.split( '-' )[ 1 ];
		return cy.get( containerId );
	}

	private getFormEditButton( formId: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.LEMMA_PAGE_SELECTORS.EDIT_BUTTON );
	}

	private getFormRemoveButton( formId: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.LEMMA_PAGE_SELECTORS.REMOVE_BUTTON );
	}

	public open( lexemeId: string ): this {
		return cy.visitTitle( 'Lexeme:' + lexemeId );
	}

	public lemmaContainer(): this {
		cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.LEMMA_LIST );
		return this;
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

}

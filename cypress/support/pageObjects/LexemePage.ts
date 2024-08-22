import Chainable = Cypress.Chainable;

export class LexemePage {

	private static get LEMMA_WIDGET_SELECTORS(): Record<string, string> {
		return { LEMMA_LIST: '.lemma-widget_lemma-list' };
	}

	private static get LEMMA_PAGE_SELECTORS(): Record<string, string> {
		return { HEADER_ID: '.wb-lexeme-header_id' };
	}

	public lemmaContainer(): this {
		cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.LEMMA_LIST );
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

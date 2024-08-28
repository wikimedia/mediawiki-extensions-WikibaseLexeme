export class NonExistingLexemePage {

	private static get NON_EXISTING_LEXEME_PAGE_SELECTORS(): Record<string, string> {
		return {
			FIRST_HEADING: '#firstHeading',
			NO_ARTICLE_TEXT: '.noarticletext'
		};
	}

	public firstHeading(): Chainable {
		return cy.get( this.constructor.NON_EXISTING_LEXEME_PAGE_SELECTORS.FIRST_HEADING );
	}

	public noArticleText(): Chainable {
		return cy.get( this.constructor.NON_EXISTING_LEXEME_PAGE_SELECTORS.NO_ARTICLE_TEXT );
	}

	public open(): this {
		cy.visitTitle( { title: 'Lexeme:L-invalid', failOnStatusCode: false } );
		return this;
	}
}

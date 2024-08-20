export class SpecialRecentChanges {

	private static get RECENT_CHANGES_SELECTORS(): Record<string, string> {
		return {
			LAST_LEXEME: '[data-target-page^="Lexeme:L"]'
		};
	}

	public open(): this {
		cy.visit( 'index.php?title=Special:RecentChanges' );
		return this;
	}

	public getLastLexeme(): Chainable {
		return cy.get( this.constructor.RECENT_CHANGES_SELECTORS.LAST_LEXEME );
	}

}

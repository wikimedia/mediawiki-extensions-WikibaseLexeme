export class SpecialRecentChanges {

	private static get RECENT_CHANGES_SELECTORS(): Record<string, string> {
		return {
			RECENT_LEXEMES: '[data-target-page^="Lexeme:L"]'
		};
	}

	public open(): this {
		cy.visit( 'index.php?title=Special:RecentChanges' );
		return this;
	}

	public getRecentLexemeChanges(): Chainable {
		return cy.get( this.constructor.RECENT_CHANGES_SELECTORS.RECENT_LEXEMES );
	}

}

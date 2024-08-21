export class SpecialWatchlistPage {

	private static get WATCHLIST_SELECTORS(): Record<string, string> {
		return {
			CHANGESLIST: '.mw-changeslist',
			CHANGESLIST_LINE_TITLE: '.mw-changeslist-line .mw-title'
		};
	}

	public open(): this {
		return cy.visitTitle( 'Special:Watchlist' );
	}

	public getTitles(): Chainable {
		return cy.get( this.constructor.WATCHLIST_SELECTORS.CHANGESLIST )
			.find( this.constructor.WATCHLIST_SELECTORS.CHANGESLIST_LINE_TITLE );
	}

}

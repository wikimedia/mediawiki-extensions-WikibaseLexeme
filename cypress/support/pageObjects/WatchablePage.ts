import Chainable = Cypress.Chainable;

export class WatchablePage {

	private static get WATCHABLE_SELECTORS(): Record<string, string> {
		return {
			CONFIRM_WATCH: '#mw-content-text button[type="submit"]'
		};
	}

	private getConfirmWatch(): Chainable {
		return cy.get( this.constructor.WATCHABLE_SELECTORS.CONFIRM_WATCH );
	}

	public watch( title: string ): Chainable {
		return cy.visitTitle( { title, qs: { action: 'watch' } } ).then( () => this.getConfirmWatch().click() );
	}
}

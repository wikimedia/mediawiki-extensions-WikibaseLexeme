import Chainable = Cypress.Chainable;

export class ItemViewPage {
	public static get SELECTORS(): object {
		return {
			STATEMENTS: '#wikibase-wbui2025-statementgrouplistview',
			VUE_CLIENTSIDE_RENDERED: '[data-v-app]',
			EDIT_LINKS: '.wikibase-wbui2025-edit-link',
			MAIN_SNAK_VALUES: '.wikibase-wbui2025-main-snak .wikibase-wbui2025-snak-value'
		};
	}

	public constructor( itemId: string ) {
		this.itemId = itemId;
	}

	public open( lang: string = 'en' ): this {
		// We force tests to be in English by default, to be able to make assertions
		// about texts (especially, for example, selecting items from a Codex MenuButton
		// menu) without needing to modify Codex components or introduce translation
		// support to Cypress.
		cy.visitTitleMobile( { title: 'Item:' + this.itemId, qs: { uselang: lang } } );
		return this;
	}

	public statementsSection(): Chainable {
		return cy.get( ItemViewPage.SELECTORS.STATEMENTS );
	}

	public editLinks(): Chainable {
		return cy.get(
			ItemViewPage.SELECTORS.VUE_CLIENTSIDE_RENDERED + ' ' + ItemViewPage.SELECTORS.EDIT_LINKS
		);
	}

	public mainSnakValues(): Chainable {
		return cy.get( ItemViewPage.SELECTORS.MAIN_SNAK_VALUES );
	}
}

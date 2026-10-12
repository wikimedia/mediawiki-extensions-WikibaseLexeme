import Chainable = Cypress.Chainable;

export class EditStatementFormPage {

	public static get SELECTORS(): object {
		return {
			MODAL_ROOT: '.wikibase-wbui2025-edit-statement-group-modal',
			FORM: '.wikibase-wbui2025-edit-statement',
			PROPERTY_NAME: '.wikibase-wbui2025-property-name > a',
			// Temporary backward-compatibility, to be cleaned up in a followup (T440572)
			SUBMIT_BUTTONS: '.wikibase-wbui2025-edit-form-actions > .cdx-button,' +
				'.wikibase-wbui2025-modal-overlay__footer__actions > .cdx-button',
			TEXT_INPUT: '.wikibase-wbui2025-edit-statement-value-input .cdx-text-input input',
			LOOKUP_INPUT: '.wikibase-wbui2025-edit-statement-value-input .cdx-lookup input',
			LOOKUP_COMPONENT: '.wikibase-wbui2025-edit-statement-value-input .cdx-lookup',
			MENU: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu',
			MENU_ITEM: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu-item',
			REFERENCES: '.wikibase-wbui2025-editable-reference',
		};
	}

	public propertyName(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.PROPERTY_NAME );
	}

	public textInput(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.TEXT_INPUT ).first();
	}

	public publishButton(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.SUBMIT_BUTTONS ).last();
	}

	public cancelButton(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.SUBMIT_BUTTONS ).first();
	}

	public lookupInput(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.LOOKUP_INPUT );
	}

	public lookupComponent(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.LOOKUP_COMPONENT );
	}

	public menu(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.MENU );
	}

	public menuItems(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.MENU_ITEM ).filter( ':visible' );
	}

	public menuItemByName( name: string ): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.MENU_ITEM )
			.filter( ':visible' )
			.contains( name ).first();
	}

	public references(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.REFERENCES );
	}

	public form(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.FORM );
	}

	public root(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.MODAL_ROOT );
	}
}

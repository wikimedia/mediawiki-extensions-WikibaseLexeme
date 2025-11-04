import Chainable = Cypress.Chainable;

export class EditStatementFormPage {

	public static get SELECTORS(): object {
		return {
			FORM: '.wikibase-wbui2025-edit-statement',
			FORM_HEADING: '.wikibase-wbui2025-edit-statement-heading',
			PROPERTY_NAME: '.wikibase-wbui2025-property-name > a',
			SUBMIT_BUTTONS: '.wikibase-wbui2025-edit-form-actions > .cdx-button',
			TEXT_INPUT: '.wikibase-wbui2025-edit-statement-value-input .cdx-text-input input',
			LOOKUP_INPUT: '.wikibase-wbui2025-edit-statement-value-input .cdx-lookup input',
			LOOKUP_COMPONENT: '.wikibase-wbui2025-edit-statement-value-input .cdx-lookup',
			MENU: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu',
			MENU_ITEM: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu-item',
			REFERENCES: '.wikibase-wbui2025-editable-reference'
		};
	}

	public propertyName(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.PROPERTY_NAME );
	}

	public formHeading(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.FORM_HEADING );
	}

	public textInput(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.TEXT_INPUT ).first();
	}

	public publishButton(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.SUBMIT_BUTTONS ).last();
	}

	public cancelButton(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.SUBMIT_BUTTONS ).first();
	}

	public lookupInput(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.LOOKUP_INPUT );
	}

	public lookupComponent(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.LOOKUP_COMPONENT );
	}

	public menu(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.MENU );
	}

	public menuItems(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.MENU_ITEM ).filter( ':visible' );
	}

	public references(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.REFERENCES );
	}

	public form(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.FORM );
	}
}

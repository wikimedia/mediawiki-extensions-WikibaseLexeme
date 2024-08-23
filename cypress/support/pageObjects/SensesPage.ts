export class SensesPage {

	private static get SENSES_CONTAINER_SELECTORS(): Record<string, string> {
		return {
			SENSES_CONTAINER: '.wikibase-lexeme-senses',
			SENSES_HEADER: '.wikibase-lexeme-senses-section h2#senses',
			SENSE_ID: '.wikibase-lexeme-sense-id',
			ADD_SENSE_LINK: '.wikibase-lexeme-senses-section ' +
				'> .wikibase-addtoolbar .wikibase-toolbar-button-add a'
		};
	}

	public static get GLOSS_WIDGET_SELECTORS(): Record<string, string> {
		return {
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			SAVE_BUTTON: '.wikibase-toolbar-button-save',
			SENSE_REMOVE_BUTTON: '.wikibase-toolbar-button-remove',
			GLOSS_REMOVE_BUTTON: '.wikibase-lexeme-sense-glosses-remove',
			ADD_BUTTON: '.lemma-widget_add',
			CANCEL_BUTTON: 'span .wikibase-toolbar-button-cancel',
			CHANGESTATE_INDICATOR: '.wikibase-edittoolbar-actionmsg',
			ADD_GLOSS_BUTTON: '.wikibase-lexeme-sense-glosses-add',
			EDIT_INPUT_VALUE: '.wikibase-lexeme-sense-gloss-value-input',
			EDIT_INPUT_LANGUAGE: '.wikibase-lexeme-sense-gloss-language-input',
			SENSE_VALUE: '.wikibase-lexeme-sense-gloss ' +
				'> .wikibase-lexeme-sense-gloss-value-cell > span',
			SENSE_LANGUAGE: '.wikibase-lexeme-sense-gloss-language',
			SENSE_ID: '.wikibase-lexeme-sense-id'
		};
	}

	private getAddSenseLink(): Chainable {
		return cy.get( this.constructor.SENSES_CONTAINER_SELECTORS.ADD_SENSE_LINK );
	}

	private getSensesContainer(): Chainable {
		return cy.get( this.constructor.SENSES_CONTAINER_SELECTORS.SENSES_CONTAINER );
	}

	private getSensesGlossWidgetInputLanguage(): Chainable {
		return this.getSensesContainer()
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE );
	}

	private setGlossWidgetInputLanguage( language: string ): this {
		this.getSensesGlossWidgetInputLanguage().clear();
		this.getSensesGlossWidgetInputLanguage().type( language );
		/* This should not be necessary, but it seems like cypress might have
		 * trouble with XHR requests firing while 'type' is running:
		 * https://github.com/cypress-io/cypress/issues/5480
		 * Likewise, *only* setting the value in the DOM doesn't work because
		 * the javascript validation sees the field as empty. So we do both.
		 */
		this.getSensesGlossWidgetInputLanguage().invoke( 'val', language );
		return this;
	}

	private getSensesGlossWidgetInputValue(): Chainable {
		return this.getSensesContainer()
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE );
	}

	private setGlossWidgetInputValue( value: string ): this {
		this.getSensesGlossWidgetInputValue().clear();
		this.getSensesGlossWidgetInputValue().type( value );
		// See comment in `setGlossWidgetInputLanguage`
		this.getSensesGlossWidgetInputValue().invoke( 'val', value );
		return this;
	}

	private getGlossWidgetSaveButton(): Chainable {
		return this.getSensesContainer()
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON );
	}

	public addSense( language: string, value: string ): this {
		this.getAddSenseLink().click();

		this.setGlossWidgetInputLanguage( language );
		this.setGlossWidgetInputValue( value );

		this.getGlossWidgetSaveButton().click();
		return this;
	}

	public getSenseIdElement(): Chainable {
		return this.getSensesContainer()
			.find( this.constructor.SENSES_CONTAINER_SELECTORS.SENSE_ID );
	}

	public getSensesHeaderElement(): Chainable {
		return cy.get( this.constructor.SENSES_CONTAINER_SELECTORS.SENSES_HEADER );
	}

}

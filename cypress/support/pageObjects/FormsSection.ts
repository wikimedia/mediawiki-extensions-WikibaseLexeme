import Chainable = Cypress.Chainable;

export class FormsSection {

	private static get FORM_WIDGET_SELECTORS(): Record<string, string> {
		return {
			ADD_FORM_LINK: '.wikibase-lexeme-forms-section > ' +
				'.wikibase-addtoolbar .wikibase-toolbar-button-add a',
			EDIT_INPUT_VALUE: '.representation-widget_representation-value-input',
			EDIT_INPUT_LANGUAGE: '.representation-widget_representation-language-input',
			FORM_SECTION_CONTAINER: '.wikibase-lexeme-forms',
			FORM_SECTION_HEADER: '.wikibase-lexeme-forms-section h2#forms',
			FORM_HEADER: '.wikibase-lexeme-form-header',
			FORM_ID: '.wikibase-lexeme-form-id',
			FORM_LIST_ITEM: '.wikibase-lexeme-form',
			GRAMMATICAL_FEATURES: '.wikibase-lexeme-form-grammatical-features-values',
			REPRESENTATION_WIDGET: '.representation-widget',
			REPRESENTATION_VALUE: '.representation-widget_representation-value',
			REPRESENTATION_LANGUAGE: '.representation-widget_representation-language'
		};
	}

	private static get FORM_PAGE_SELECTORS(): Record<string, string> {
		return {
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove',
			SAVE_BUTTON: '.wikibase-toolbar-button-save'
		};
	}

	public getAddFormLink(): Chainable {
		return cy.get( this.constructor.FORM_WIDGET_SELECTORS.ADD_FORM_LINK );
	}

	public getFormWidgetSaveButton(): Chainable {
		return this.getFormsContainer()
			.find( this.constructor.FORM_PAGE_SELECTORS.SAVE_BUTTON );
	}

	private getFormWidgetInputLanguage(): Chainable {
		return this.getFormsContainer()
			.find( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE );
	}

	private setFormWidgetInputLanguage( language: string ): this {
		this.getFormWidgetInputLanguage().clear();
		this.getFormWidgetInputLanguage().type( language );
		/* This should not be necessary, but it seems like cypress might have
		 * trouble with XHR requests firing while 'type' is running:
		 * https://github.com/cypress-io/cypress/issues/5480
		 * Likewise, *only* setting the value in the DOM doesn't work because
		 * the javascript validation sees the field as empty. So we do both.
		 */
		this.getFormWidgetInputLanguage().invoke( 'val', language );
		return this;
	}

	private getFormWidgetInputValue(): Chainable {
		return this.getFormsContainer()
			.find( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE );
	}

	private setFormWidgetInputValue( value: string ): this {
		this.getFormWidgetInputValue().clear();
		this.getFormWidgetInputValue().type( value );
		// See comment in `setFormWidgetInputLanguage`
		this.getFormWidgetInputValue().invoke( 'val', value );
		return this;
	}

	public addForm( language: string, value: string ): this {
		this.getAddFormLink().click();

		this.setFormWidgetInputLanguage( language );
		this.setFormWidgetInputValue( value );

		this.getFormWidgetSaveButton().click();
		return this;
	}

	private getNthForm( index: number ): Chainable {
		return this.getFormsContainer()
			.find( this.constructor.FORM_WIDGET_SELECTORS.FORM_LIST_ITEM )
			.eq( index );
	}

	public getNthFormData( index: number ): Chainable {
		return this.getNthForm( index )
			.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_VALUE )
			.then( ( valueElement ) => this.getNthForm( index )
				.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_LANGUAGE )
				.then( ( languageElement ) => this.getNthForm( index )
					.find( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES )
					.then( ( grammaticalFeaturesElement ) => cy.wrap( {
						value: valueElement.text().trim(),
						language: languageElement.text().trim(),
						grammaticalFeatures: grammaticalFeaturesElement.text().trim()
					} ) )
				)
			);
	}

	/**
	 * @param formId If provided, get a specific form item. When omitted, gets all.
	 */
	public getFormListItem( formId?: string ): Chainable {
		if ( typeof formId === 'undefined' ) {
			return cy.get( this.constructor.FORM_WIDGET_SELECTORS.FORM_LIST_ITEM );
		} else {
			// an example formId: L516-F1
			// the part after the '-' is also the element id for the form's container
			const containerId = '#' + formId.split( '-' )[ 1 ];
			return cy.get( containerId );
		}
	}

	public getFormId( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.FORM_ID );
	}

	public getFormEditButton( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_PAGE_SELECTORS.EDIT_BUTTON );
	}

	public getFormsHeader(): Chainable {
		return cy.get( this.constructor.FORM_WIDGET_SELECTORS.FORM_SECTION_HEADER );
	}

	public getFormsContainer(): Chainable {
		return cy.get( this.constructor.FORM_WIDGET_SELECTORS.FORM_SECTION_CONTAINER );
	}

	public getGrammaticalFeatureList( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES );
	}

	public getRepresentationWidget( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_WIDGET );
	}

	public getRepresentationLanguage( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_LANGUAGE );
	}

	public getFormRemoveButton( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_PAGE_SELECTORS.REMOVE_BUTTON );
	}

	public removeForm( formId: string ): this {
		this.getFormEditButton( formId ).click();
		this.getFormRemoveButton( formId ).click();
		this.getFormListItem( formId ).should( 'not.exist' );
		return this;
	}

}

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
			FORM_STATEMENT_LIST: '.wikibase-lexeme-form-body ' +
				'.wikibase-statementgrouplistview .wikibase-listview',
			ADD_STATEMENT_TO_FORM: '.wikibase-statementgrouplistview ' +
				'.wikibase-toolbar-button-add a',
			SNAK_PROPERTY_ID_INPUT: '.wikibase-statementgroupview ' +
				'.wikibase-snakview-property input',
			SNAK_VALUE_INPUT: '.wikibase-listview #new .wikibase-snakview-value-container ' +
				'.valueview-value .valueview-input',
			STATEMENT_GROUP_VIEW: '.wikibase-statementgroupview',
			STATEMENT_VALUE: '.wikibase-snakview-body ' +
				'.wikibase-snakview-variation-valuesnak  .valueview-instaticmode',
			GRAMMATICAL_FEATURES: '.wikibase-lexeme-form-grammatical-features-values',
			ADD_REPRESENTATION_BUTTON: '.representation-widget_add',
			REMOVE_REPRESENTATION_BUTTON: '.representation-widget_representation-remove',
			REPRESENTATION_LIST: '.representation-widget_representation-list',
			REPRESENTATION_EDIT_BOX: '.representation-widget_representation-edit-box',
			REPRESENTATION_WIDGET: '.representation-widget',
			REPRESENTATION_VALUE: '.representation-widget_representation-value',
			REPRESENTATION_LANGUAGE: '.representation-widget_representation-language'
		};
	}

	private static get FORM_PAGE_SELECTORS(): Record<string, string> {
		return {
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove',
			SAVE_BUTTON: '.wikibase-toolbar-button-save',
			CANCEL_BUTTON: '.wikibase-toolbar-button-cancel'
		};
	}

	private static get OOUI_ELEMENT_SELECTORS(): Record<string, string> {
		return {
			TAG_ITEM: '.oo-ui-tagItemWidget',
			BUTTON_ELEMENT: '.oo-ui-buttonElement-button',
			LABEL_ELEMENT: '.oo-ui-labelElement',
			MENU_ITEM: '.ui-ooMenu-item'
		};
	}

	public getAddFormLink(): Chainable {
		return cy.get( this.constructor.FORM_WIDGET_SELECTORS.ADD_FORM_LINK );
	}

	public getAddFormCancelLink(): Chainable {
		return cy.get( this.constructor.FORM_PAGE_SELECTORS.CANCEL_BUTTON );
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

	public openAddFormForm(): this {
		this.getAddFormLink().click();
		return this;
	}

	public cancelAddForm(): this {
		this.getAddFormCancelLink().click();
		return this;
	}

	public addForm( language: string, value: string ): this {
		this.openAddFormForm();

		this.setFormWidgetInputLanguage( language );
		this.setFormWidgetInputValue( value );

		this.getFormWidgetSaveButton().click();
		this.getFormWidgetSaveButton().should( 'not.exist' );
		return this;
	}

	private getNthForm( index: number ): Chainable {
		return this.getFormsContainer()
			.find( this.constructor.FORM_WIDGET_SELECTORS.FORM_LIST_ITEM )
			.eq( index );
	}

	public getNthFormLastRepresentationData( index: number ): Chainable {
		return this.getNthForm( index )
			.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_VALUE )
			.then( ( valueElement ) => this.getNthForm( index )
				.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_LANGUAGE )
				.then( ( languageElement ) => this.getNthForm( index )
					.find( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES )
					.then( ( grammaticalFeaturesElement ) => cy.wrap( {
						value: valueElement.last().text().trim(),
						language: languageElement.last().text().trim(),
						grammaticalFeatures: grammaticalFeaturesElement.last().text().trim()
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

	private clickAddRepresentationButton( formId: string ): this {
		this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.ADD_REPRESENTATION_BUTTON )
			.click();
		return this;
	}

	private getRepresentationEditFormRepresentationsList( formId: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_LIST )
			.find( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_EDIT_BOX );
	}

	private getRepresentationEditFormSaveButton( formId: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_PAGE_SELECTORS.SAVE_BUTTON );
	}

	private submitForm( formId: string ): this {
		this.getRepresentationEditFormSaveButton( formId )
			.invoke( 'attr', 'aria-disabled' )
			.should( 'eq', 'false' );
		this.getRepresentationEditFormSaveButton( formId ).click();
		this.getRepresentationEditFormSaveButton( formId ).should( 'not.exist' );
		return this;
	}

	private getLastRepresentationEditFormInputValue( formId: string ): Chainable {
		return this.getRepresentationEditFormRepresentationsList( formId )
			.last()
			.find( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE );
	}

	public getLastRepresentationEditFromInputLanguage( formId?: string ): Chainable {
		return this.getRepresentationEditFormRepresentationsList( formId )
			.last()
			.find( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE );
	}

	private getLastRepresentationEditFormRemoveButton( formId: string ): Chainable {
		return this.getRepresentationEditFormRepresentationsList( formId )
			.last()
			.find( this.constructor.FORM_WIDGET_SELECTORS.REMOVE_REPRESENTATION_BUTTON );
	}

	public addRepresentationToForm(
		formId: string,
		representation: string,
		language: string,
		submitImmediately: boolean = true
	): this {
		this.getFormEditButton( formId ).click();
		this.clickAddRepresentationButton( formId );

		this.getLastRepresentationEditFormInputValue( formId ).clear();
		this.getLastRepresentationEditFormInputValue( formId ).type( representation );
		this.getLastRepresentationEditFromInputLanguage( formId ).clear();
		this.getLastRepresentationEditFromInputLanguage( formId ).type( language );

		if ( submitImmediately ) {
			this.submitForm( formId );
		}
		return this;
	}

	public editRepresentationFormHasInvalidLanguageInput( formId: string ): Chainable {
		return this.getLastRepresentationEditFromInputLanguage( formId )
			.invoke( 'attr', 'aria-invalid' )
			.should( 'eq', 'true' );
	}

	public editRepresentationOfForm(
		formId: string,
		representation: string,
		language: string,
		submitImmediately: boolean = true
	): this {
		this.getFormEditButton( formId ).click();

		this.getLastRepresentationEditFormInputValue( formId ).clear();
		this.getLastRepresentationEditFormInputValue( formId ).type( representation );
		this.getLastRepresentationEditFromInputLanguage( formId ).clear();
		this.getLastRepresentationEditFromInputLanguage( formId ).type( language );

		if ( submitImmediately ) {
			this.submitForm( formId );
		}
		return this;
	}

	public removeLastRepresentationOfForm( formId: string ): this {
		this.getFormEditButton( formId ).click();

		this.getLastRepresentationEditFormRemoveButton( formId ).click();
		this.submitForm( formId );

		return this;
	}

	public addGrammaticalFeatureToForm(
		formId: string,
		grammaticalFeatureId: string,
		submitImmediately: boolean = true
	): this {
		this.getFormEditButton( formId ).click();

		this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES )
			.type( grammaticalFeatureId );

		this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES )
			.find( this.constructor.OOUI_ELEMENT_SELECTORS.LABEL_ELEMENT )
			.first()
			.click();

		if ( submitImmediately ) {
			this.submitForm( formId );
		}
		return this;
	}

	public removeFirstGrammaticalFeatureFromForm(
		formId: string,
		submitImmediately: boolean = true
	): this {
		this.getFormEditButton( formId ).click();

		this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES )
			.find( this.constructor.OOUI_ELEMENT_SELECTORS.TAG_ITEM )
			.first()
			.find( this.constructor.OOUI_ELEMENT_SELECTORS.BUTTON_ELEMENT )
			.click();

		if ( submitImmediately ) {
			this.submitForm( formId );
		}
		return this;
	}

	public getFormRemoveButton( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_PAGE_SELECTORS.REMOVE_BUTTON );
	}

	public getFormStatementList( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.FORM_STATEMENT_LIST );
	}

	public getFormStatement( formId?: string ): Chainable {
		return this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.STATEMENT_GROUP_VIEW )
			.invoke( 'attr', 'id' )
			.then( ( propertyIdAttr ) => this.getFormListItem( formId )
				.find( this.constructor.FORM_WIDGET_SELECTORS.STATEMENT_VALUE )
				.then( ( valueElement ) => cy.wrap( {
					propertyId: propertyIdAttr.split( '-' ),
					value: valueElement.text()
				} ) )
			);
	}

	public addStatementToForm(
		formId: string,
		statementPropertyId: string,
		statementValue: string
	): this {
		this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.ADD_STATEMENT_TO_FORM )
			.click();

		this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.SNAK_PROPERTY_ID_INPUT )
			.type( statementPropertyId );
		cy.get( this.constructor.OOUI_ELEMENT_SELECTORS.MENU_ITEM )
			.first()
			.click();

		this.getFormListItem( formId )
			.find( this.constructor.FORM_WIDGET_SELECTORS.SNAK_VALUE_INPUT )
			.type( statementValue );

		this.submitForm( formId );

		return this;
	}

	public removeForm( formId: string ): this {
		this.getFormEditButton( formId ).click();
		this.getFormRemoveButton( formId ).click();
		this.getFormListItem( formId ).should( 'not.exist' );
		return this;
	}

}

export class SensesSection {

	private static get SENSES_CONTAINER_SELECTORS(): Record<string, string> {
		return {
			SENSES_CONTAINER: '.wikibase-lexeme-senses',
			SENSES_HEADER: '.wikibase-lexeme-senses-section h2#senses',
			SENSE_ID: '.wikibase-lexeme-sense-id',
			SENSE: '.wikibase-lexeme-sense',
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

	public removeSense( index: number ): this {
		this.getNthSense( index )
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_REMOVE_BUTTON )
			.click();
		return this;
	}

	public getSenses(): Chainable {
		return this.getSensesContainer()
			.find( this.constructor.SENSES_CONTAINER_SELECTORS.SENSE );
	}

	public getSensesHeaderElement(): Chainable {
		return cy.get( this.constructor.SENSES_CONTAINER_SELECTORS.SENSES_HEADER );
	}

	public startEditingNthSense( index: number ): this {
		this.getNthSense( index )
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_BUTTON ).click();
		return this;
	}

	public editSenseNoSubmit( index: number, value: string ): this {
		this.startEditingNthSense( index );
		this.setSenseInputFormValue( value );
		return this;
	}

	public cancelSenseEditing( index: number ): this {
		this.getNthSense( index )
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.CANCEL_BUTTON ).click();
		return this;
	}

	private getNthSense( index: number ): Chainable {
		return this.getSensesContainer()
			.find( this.constructor.SENSES_CONTAINER_SELECTORS.SENSE )
			.eq( index );
	}

	private getGlossEditInputValue(): Chainable {
		return this.getSensesContainer()
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE );
	}

	public setSenseInputFormValue( value: string ): this {
		this.getGlossEditInputValue().clear();
		this.getGlossEditInputValue().type( value );
		this.getGlossEditInputValue().invoke( 'val', value );
		return this;
	}

	public submitNthSense( index: number ): this {
		this.getNthSense( index )
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON )
			.click();
		return this;
	}

	public editSenseValueAndSubmit( index: number, value: string ): this {
		this.startEditingNthSense( index );
		this.setSenseInputFormValue( value );
		this.submitNthSense( index );
		return this;
	}

	public getNthSenseData( index: number ): Chainable {
		return this.getNthSense( index )
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_VALUE )
			.then( ( valueElement ) => this.getNthSense( index )
				.find( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_LANGUAGE )
				.then( ( languageElement ) => this.getNthSense( index )
					.find( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_ID )
					.then( ( senseIdElement ) => cy.wrap( {
						value: valueElement.text().trim(),
						language: languageElement.text().trim(),
						senseIdElement: senseIdElement
					} ) )
				)
			);
	}

	public isNthSenseSubmittable( index: number ): Chainable {
		return this.getNthSense( index )
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON )
			.first().invoke( 'attr', 'aria-disabled' )
			.then( ( attr ) => cy.wrap( { submittable: ( attr !== 'true' ) } ) );
	}

	public addGlossToNthSense(
		index: number,
		gloss: string,
		language: string,
		submitImmediately: boolean
	): this {
		this.startEditingNthSense( index );
		this.getNthSense( index )
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.ADD_GLOSS_BUTTON ).click();
		this.getNthSense( index )
			.find( '.wikibase-lexeme-sense-glosses-table' )
			.find( '.wikibase-lexeme-sense-gloss' )
			.then( ( glosses ) => {
				const newGlossIndex = glosses.length - 1;
				const newGloss = glosses[ newGlossIndex ];

				cy.wrap( newGloss )
					.find( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE )
					.clear().type( language ).invoke( 'val', language );
				cy.wrap( newGloss )
					.find( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE )
					.clear().type( gloss ).invoke( 'val', gloss );

				if ( submitImmediately !== false ) {
					this.submitNthSense( index );
				}
			} );
		return this;
	}

	public removeGloss( index: number, submitImmediately: boolean ): this {
		this.getNthSense( index )
			.find( '.wikibase-lexeme-sense-glosses-table' )
			.find( '.wikibase-lexeme-sense-gloss' )
			.find( this.constructor.GLOSS_WIDGET_SELECTORS.GLOSS_REMOVE_BUTTON )
			.first().click();
		if ( submitImmediately !== false ) {
			this.submitNthSense( index );
		}
		return this;
	}

	public getNthSenseFormValues( index: number ): Chainable {
		return this.getNthSense( index )
			.find( '.wikibase-lexeme-sense-glosses-table' )
			.find( '.wikibase-lexeme-sense-gloss' )
			.then( ( $glossList ) => Cypress.$.map(
				$glossList.toArray(),
				( gloss ) => {
					const language = Cypress.$( gloss )
						.find( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE )
						.val();
					const value = Cypress.$( gloss )
						.find( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE )
						.val();
					return { language, value };
				} ) ).then( ( list ) => ( { glosses: list } ) );
	}

}

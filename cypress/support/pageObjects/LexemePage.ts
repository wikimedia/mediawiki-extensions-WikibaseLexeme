import Chainable = Cypress.Chainable;

export class LexemePage {

	private static get LEMMA_WIDGET_SELECTORS(): Record<string, string> {
		return {
			EDIT_BUTTON: '.lemma-widget_edit',
			EDIT_INPUT_VALUE: '.lemma-widget_lemma-value-input',
			EDIT_INPUT_LANGUAGE: '.lemma-widget_lemma-language-input',
			EDIT_INPUT_LEXEME_LANGUAGE: '#lexeme-language',
			EDIT_INPUT_LEXEME_LEXICAL_CATEGORY: '#lexeme-lexical-category',
			ADD_BUTTON: '.lemma-widget_add',
			SAVE_BUTTON: '.lemma-widget_save',
			LEMMA_LIST: '.lemma-widget_lemma-list',
			LEMMA_EDIT_BOX: '.lemma-widget_lemma-edit-box',
			REDUNDANT_LANGUAGE_WARNING: '.lemma-widget_redundant-language-warning'
		};
	}

	private static get LEMMA_PAGE_SELECTORS(): Record<string, string> {
		return {
			HEADER_ID: '.wb-lexeme-header_id',
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove'
		};
	}

	private static get STATEMENT_SELECTORS(): Record<string, string> {
		return {
			MAIN_STATEMENTS_CONTAINER:
				'.wikibase-entityview-main > .wikibase-statementgrouplistview',
			ADD_MAIN_STATEMENT_LINK: '.wikibase-addtoolbar > span > a',
			EDIT_PROPERTY_INPUT: '.wikibase-snakview-property input',
			EDIT_VALUE_INPUT: '.valueview-input',
			STATEMENT_VALUE: '.wikibase-snakview-value'
		};
	}

	private static get OOUI_SELECTORS(): Record<string, string> {
		return {
			VISIBLE_ENTITY_SUGGESTION: 'ul.ui-suggester-list li'
		};
	}

	private static get WIKIBASE_TOOLBAR_SELECTORS(): Record<string, string> {
		return {
			SAVE_BUTTON: '.wikibase-toolbar-button-save'
		};
	}

	private static get MEDIAWIKI_PAGE_SELECTORS(): Record<string, string> {
		return {
			VIEW_HISTORY_LINK: '#right-navigation #p-views #ca-history a',
			RESTORE_REVISION_LINK: 'a[href*=restore]',
			UNDO_OR_RESTORE_SAVE_PAGE_BUTTON:
				'#bodyContent #mw-content-text .editOptions .editButtons button',
			UNDO_REVISION_LINK: '#mw-content-text #pagehistory li .mw-history-undo a'
		};
	}

	public getRedundantLanguageWarning(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.REDUNDANT_LANGUAGE_WARNING );
	}

	private getLemmaEditBoxes(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.LEMMA_EDIT_BOX );
	}

	private getLemmaWidgetAddButton(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.ADD_BUTTON );
	}

	public getLemmaContainer(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.LEMMA_LIST );
	}

	public getMainStatementsContainer(): Chainable {
		return cy.get( this.constructor.STATEMENT_SELECTORS.MAIN_STATEMENTS_CONTAINER );
	}

	public getAddMainStatementLink(): Chainable {
		return this.getMainStatementsContainer().find(
			this.constructor.STATEMENT_SELECTORS.ADD_MAIN_STATEMENT_LINK
		);
	}

	public getLexemeLanguageInput(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LEXEME_LANGUAGE );
	}

	public getLexemeLexicalCategoryInput(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LEXEME_LEXICAL_CATEGORY );
	}

	public getHeaderSaveButton(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.SAVE_BUTTON );
	}

	public getStatementPropertyInput(): Chainable {
		return this.getMainStatementsContainer().find(
			this.constructor.STATEMENT_SELECTORS.EDIT_PROPERTY_INPUT
		);
	}

	public getStatementValueInput(): Chainable {
		return this.getMainStatementsContainer().find(
			this.constructor.STATEMENT_SELECTORS.EDIT_VALUE_INPUT
		);
	}

	public getStatementValueElement(): Chainable {
		return this.getMainStatementsContainer().find(
			this.constructor.STATEMENT_SELECTORS.STATEMENT_VALUE
		);
	}

	public getStatementSaveButton(): Chainable {
		return this.getMainStatementsContainer().find(
			this.constructor.WIKIBASE_TOOLBAR_SELECTORS.SAVE_BUTTON
		);
	}

	public getHeaderId(): Chainable<string> {
		return cy.get( this.constructor.LEMMA_PAGE_SELECTORS.HEADER_ID )
			.then(
				( element ) => element
					.text()
					.replace( /[^L0-9]/g, '' )
			);
	}

	public open( lexemeId: string ): Chainable {
		const title = 'Lexeme:' + lexemeId;
		return cy.visitTitle( title );
	}

	public addMainStatement( propertyId: string, value: string ): this {
		this.getAddMainStatementLink().click();
		this.getStatementPropertyInput().clear();
		this.getStatementPropertyInput().type( propertyId );
		this.selectFirstSuggestedEntityOnEntitySelector();

		this.getStatementValueInput().clear();
		this.getStatementValueInput().type( value );

		this.getStatementSaveButton().invoke( 'attr', 'aria-disabled' ).should( 'not.eq', 'true' );
		this.getStatementSaveButton().click();
		this.getStatementSaveButton().should( 'not.exist' );

		return this;
	}

	public startHeaderEditMode(): this {
		cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON ).click();
		this.getLexemeLanguageInput().invoke( 'val' ).should( 'not.be.empty' );
		return this;
	}

	public setLexemeLanguageToItem( item: string ): this {
		this.getLexemeLanguageInput().clear();
		this.getLexemeLanguageInput().type( item );
		return this;
	}

	public setLexemeLexicalCategoryToItem( item: string ): this {
		this.getLexemeLexicalCategoryInput().clear();
		this.getLexemeLexicalCategoryInput().type( item );
		return this;
	}

	public selectFirstSuggestedEntityOnEntitySelector(): this {
		cy.get( this.constructor.OOUI_SELECTORS.VISIBLE_ENTITY_SUGGESTION )
			.filter( ':visible' ).click();
		return this;
	}

	public clickHeaderSaveButton(): this {
		this.getHeaderSaveButton().not( ':disabled' ).click();
		this.headerSaveButtonNotPresent();
		return this;
	}

	public headerSaveButtonNotPresent(): Chainable {
		return cy.get( this.constructor.LEMMA_WIDGET_SELECTORS.SAVE_BUTTON ).should( 'not.exist' );
	}

	public setLexemeLanguageItem( item: string ): this {
		this.setLexemeLanguageToItem( item );
		this.selectFirstSuggestedEntityOnEntitySelector();
		this.clickHeaderSaveButton();
		return this;
	}

	public setLexicalCategoryItem( item: string ): this {
		this.setLexemeLexicalCategoryToItem( item );
		this.selectFirstSuggestedEntityOnEntitySelector();
		this.clickHeaderSaveButton();
		return this;
	}

	public setNthLemma( position: number, lemmaText: string, languageCode: string ): this {
		this.startHeaderEditMode();
		this.fillNthLemma( position, lemmaText, languageCode );
		this.clickHeaderSaveButton();
		this.getLemmaEditBoxes().should( 'not.exist' );
		return this;
	}

	public fillNthLemma( position: number, lemmaText: string, languageCode: string ): this {
		this.getLemmaEditBoxes().then( ( lemmaBoxes ) => {
			const lemmaBoxesCount = lemmaBoxes.length;

			if ( lemmaBoxesCount - 1 < position ) {
				this.getLemmaWidgetAddButton().click();
				this.getLemmaEditBoxes().should( 'have.length', lemmaBoxesCount + 1 );
				this.fillNthLemma( position, lemmaText, languageCode );
			} else {
				this.getLemmaEditBoxes()
					.eq( position )
					.find( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_VALUE )
					.as( 'nthInputValue' );
				cy.get( '@nthInputValue' ).clear();
				cy.get( '@nthInputValue' ).type( lemmaText );

				this.getLemmaEditBoxes()
					.eq( position )
					.find( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE )
					.as( 'nthInputLanguage' );
				cy.get( '@nthInputLanguage' ).clear();
				cy.get( '@nthInputLanguage' ).type( languageCode );
			}
		} );
		return this;
	}

	private viewHistoryLink(): Chainable {
		return cy.get( this.constructor.MEDIAWIKI_PAGE_SELECTORS.VIEW_HISTORY_LINK );
	}

	private restoreRevisionLink(): Chainable {
		return cy.get( this.constructor.MEDIAWIKI_PAGE_SELECTORS.RESTORE_REVISION_LINK );
	}

	private undoOrRestoreSavePageButton(): Chainable {
		return cy.get(
			this.constructor.MEDIAWIKI_PAGE_SELECTORS.UNDO_OR_RESTORE_SAVE_PAGE_BUTTON
		);
	}

	private undoRevisionLink(): Chainable {
		return cy.get( this.constructor.MEDIAWIKI_PAGE_SELECTORS.UNDO_REVISION_LINK );
	}

	public restorePreviousRevision(): this {
		this.viewHistoryLink().click( { force: true } );
		this.restoreRevisionLink().click();
		this.undoOrRestoreSavePageButton().click();
		return this;
	}

	public undoLatestRevision(): this {
		this.viewHistoryLink().click( { force: true } );
		this.undoRevisionLink().click();
		this.undoOrRestoreSavePageButton().click();
		return this;
	}

}

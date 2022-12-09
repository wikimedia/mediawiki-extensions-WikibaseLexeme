'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	_ = require( 'lodash' ),
	MixinBuilder = require( 'wdio-wikibase/pagesections/mixinbuilder' ),
	MainStatementSection = require( 'wdio-wikibase/pagesections/main.statement.section' ),
	ComponentInteraction = require( 'wdio-wikibase/pagesections/ComponentInteraction' );

class LexemePage extends MixinBuilder.mix( Page ).with( MainStatementSection, ComponentInteraction ) {

	static get LEMMA_WIDGET_SELECTORS() {
		return {
			EDIT_BUTTON: '.lemma-widget_edit',
			SAVE_BUTTON: '.lemma-widget_save',
			ADD_BUTTON: '.lemma-widget_add',
			EDIT_INPUT_VALUE: '.lemma-widget_lemma-value-input',
			EDIT_INPUT_LANGUAGE: '.lemma-widget_lemma-language-input',
			EDIT_INPUT_LEXEME_LANGUAGE: '#lexeme-language',
			EDIT_INPUT_LEXEME_LEXICAL_CATEGORY: '#lexeme-lexical-category'
		};
	}

	static get FORM_WIDGET_SELECTORS() {
		return {
			REPRESENTATIONS: '.wikibase-lexeme-form-header .representation-widget .representation-widget_representation-list li',
			REPRESENTATION_VALUE: '.wikibase-lexeme-form-header .representation-widget_representation-value',
			REPRESENTATION_LANGUAGE: '.wikibase-lexeme-form-header .representation-widget_representation-language',
			EDIT_INPUT_VALUE: '.representation-widget_representation-value-input',
			EDIT_INPUT_LANGUAGE: '.representation-widget_representation-language-input',
			GRAMMATICAL_FEATURES: '.wikibase-lexeme-form-grammatical-features-values',
			ADD_REPRESENTATION_BUTTON: '.representation-widget_add',
			ADD_STATEMENT_TO_FORM: '.wikibase-statementgrouplistview .wikibase-toolbar-button-add a',
			REMOVE_REPRESENTATION_BUTTON: '.representation-widget_representation-remove',
			FORM_STATEMENT_LIST: '.wikibase-lexeme-form-body .wikibase-statementgrouplistview .wikibase-listview'
		};
	}

	static get GENERIC_TOOLBAR_SELECTORS() {
		return {
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove',
			SAVE_BUTTON: '.wikibase-toolbar-button-save',
			CANCEL_BUTTON: '.wikibase-toolbar-button-cancel'
		};
	}

	static get PERSONAL_BAR() {
		return {
			USER_NOT_LOGIN_ICON: '#pt-anonuserpage',
			USER_TOOLBAR: '#p-personal'
		};
	}

	get lemmaContainer() {
		return $( '.lemma-widget_lemma-list' );
	}

	get lemmas() {
		return this.lemmaContainer.$$( '.lemma-widget_lemma-edit-box' );
	}

	get formsContainer() {
		return $( '.wikibase-lexeme-forms' );
	}

	get forms() {
		return this.formsContainer.$$( '.wikibase-lexeme-form' );
	}

	get formClaimValueInputField() {
		return $( '.wikibase-listview #new .wikibase-snakview-value-container .valueview-value .valueview-input' );
	}

	get viewHistoryLink() {
		return $( '#right-navigation #p-views #ca-history a' );
	}

	get restoreRevisionLink() {
		return $( 'a[href*=restore]' ); // This link doesn't have any css identifier
	}

	get undoRevisionLink() {
		return $( '#mw-content-text #pagehistory li .mw-history-undo a' );
	}

	get undoOrRestoreSavePageButton() {
		return $( '#bodyContent #mw-content-text .editOptions .editButtons button' ); // submit undo on the Undoing edit page
	}

	get addFormCancelLink() {
		return $( this.constructor.GENERIC_TOOLBAR_SELECTORS.CANCEL_BUTTON );
	}

	get headerId() {
		return $( '.wb-lexeme-header_id' ).getText().replace( /[^L0-9]/g, '' ); // remove non-marked-up styling text "(L123)"
	}

	get formId() {
		return $( '.wikibase-lexeme-form-header > .wikibase-lexeme-form-id' );
	}

	get hasFormHeader() {
		return $( '.wikibase-lexeme-forms-section h2#forms' ).isExisting();
	}

	get addFormLink() {
		return $( '.wikibase-lexeme-forms-section > .wikibase-addtoolbar .wikibase-toolbar-button-add a' );
	}

	get hasGramaticalFeatureList() {
		return $( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES ).isExisting();
	}

	get hasRepresentation() {
		return $( '.wikibase-lexeme-form-header .representation-widget' ).isExisting();
	}

	get formStatementList() {
		return $( this.constructor.FORM_WIDGET_SELECTORS.FORM_STATEMENT_LIST );
	}

	get headerSaveButton() {
		return $( this.constructor.LEMMA_WIDGET_SELECTORS.SAVE_BUTTON );
	}

	get lexemeLanguageInput() {
		return $( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LEXEME_LANGUAGE );
	}

	get lexemeLexicalCategoryInput() {
		return $( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LEXEME_LEXICAL_CATEGORY );
	}

	/**
	 * Open the given Lexeme page
	 *
	 * @param {string} lexemeId
	 */
	open( lexemeId ) {
		const title = 'Lexeme:' + lexemeId;
		super.openTitle( title );
		try {
			$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON ).waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		} catch ( e ) {
			// reload and try again once, in case the lexeme is new
			// and the first load hit a lagged replica (T232364)
			super.openTitle( title );
			$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON ).waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		}
		this.addFormLink.waitForDisplayed( { timeout: browser.config.nonApiTimeout } ); // last button on page, probably the last
	}

	/**
	 * @param {string} lemmaText
	 * @param {string} languageCode
	 */
	setFirstLemma( lemmaText, languageCode ) {
		this.startHeaderEditMode();

		this.fillNthLemma( 0, lemmaText, languageCode );

		this.headerSaveButton.click();
		this.headerSaveButton.waitForExist( { reverse: true } );
	}

	startHeaderEditMode() {
		$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON ).click();
		browser.waitUntil( () => {
			return this.lexemeLanguageInput.getValue();
		} );
	}

	fillNthLemma( position, lemmaText, languageCode ) {
		for ( let i = this.lemmas.length; i <= position; i++ ) {
			const addButton = $( this.constructor.LEMMA_WIDGET_SELECTORS.ADD_BUTTON );
			addButton.waitForClickable( { timeout: browser.config.nonApiTimeout } );
			addButton.click();
		}

		const lemma = this.lemmas[ position ];
		lemma.$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( lemmaText );
		lemma.$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( languageCode );
	}

	/**
	 * Set the language for the lexeme
	 *
	 * This will retry entering the id if it isn't found immeadietly, because cirrus and database replicas might be slow
	 * to update.
	 *
	 * @param {string} item Item ID to enter into the field
	 */
	setLexemeLanguageItem( item ) {
		this.lexemeLanguageInput.setValue( item );
		this.selectFirstSuggestedEntityOnEntitySelector();

		browser.waitUntil( () => {
			if ( this.waitTillHeaderIsSaveableOrError() ) {
				return true;
			}
			this.waitAndRetryInput( this.lexemeLanguageInput, item );
			return false;
		} );

		this.headerSaveButton.click();
		this.headerSaveButton.waitForExist( { reverse: true } );
	}

	/**
	 * Set the lexical category for the lexeme
	 *
	 * This will retry entering the id if it isn't found immeadietly, because cirrus and database replicas might be slow
	 * to update.
	 *
	 * @param {string} item Item ID to enter into the field
	 */
	setLexicalCategoryItem( item ) {
		this.lexemeLexicalCategoryInput.setValue( item );
		this.selectFirstSuggestedEntityOnEntitySelector();

		browser.waitUntil( () => {
			if ( this.waitTillHeaderIsSaveableOrError() ) {
				return true;
			}
			this.waitAndRetryInput( this.lexemeLexicalCategoryInput, item );
			return false;
		} );

		this.headerSaveButton.click();
		this.headerSaveButton.waitForExist( { reverse: true } );
	}

	/**
	 * Wait for a period of time and then enter a value into an input
	 *
	 * @param input the wdio input element into which to enter the value
	 * @param value value to be entered into the element
	 * @param {number} [timeoutMS] duration to wait in ms, default 1000 ms
	 * @private
	 */
	waitAndRetryInput( input, value, timeoutMS ) {
		browser.call( () =>
			new Promise( ( resolve ) => {
				setTimeout( resolve, timeoutMS || 1000 );
			} ).then( () => {
				input.setValue( value );
			} )
		);
	}

	/**
	 * Wait until the Lexeme header is saveable or there is an error message
	 *
	 * @return {boolean} true if saveable, false if there is an error message
	 * @private
	 */
	waitTillHeaderIsSaveableOrError() {
		let isSaveable = false;
		browser.waitUntil( () => {
			if ( this.isHeaderSubmittable() ) {
				isSaveable = true;
				return true;
			}
			const errorMsg = $( 'body > ul.ui-entityselector-list > .ui-entityselector-notfound' );
			return errorMsg.isDisplayed();
		} );
		return isSaveable;
	}

	isHeaderSubmittable() {
		return this.headerSaveButton.isEnabled();
	}

	/**
	 * Add a form
	 *
	 * @param {string} value
	 * @param {string} language
	 */
	addForm( value, language ) {
		browser.clickTillItExists(
			this.addFormLink,
			this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ),
			'Failed to make the "Add Form" inputs exist by clicking the addForm link'
		);

		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );
		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		this.formsContainer.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON ).click();

		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE )
			.waitForExist( { reverse: true } );
	}

	/**
	 * Remove the nth for on the page
	 *
	 * @param {number} index
	 */
	removeNthForm( index ) {
		this.startEditingNthForm( index );

		const form = this.forms[ index ];
		const removeButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.REMOVE_BUTTON );

		removeButton.waitForClickable();
		removeButton.click();
		form.waitForExist( { reverse: true } );
	}

	/**
	 * Get data of the nth form on the page
	 * Gets only the first form representation value
	 *
	 * @param {number} index
	 * @return {{value, language, grammaticalFeatures}}
	 */
	getNthFormData( index ) {
		const form = this.forms[ index ];

		return {
			value: form.$( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_VALUE ).getText(),
			language: form.$( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_LANGUAGE ).getText(),
			grammaticalFeatures: form.$( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES ).getText()
		};
	}

	/**
	 * Get data of the nth form on the page
	 *
	 * @param {number} index
	 * @return {{value, language}}
	 */
	getNthFormFormValues( index ) {
		const form = this.forms[ index ],
			languageFields = form.$$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ),
			representationInputs = [];

		_.each( form.$$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ), function ( element, key ) {
			representationInputs.push( {
				value: element.getValue(),
				language: languageFields[ key ].getValue()
			} );
		} );

		return {
			representations: representationInputs
		};
	}

	getNthFormFormValuesAfterSave( index ) {
		const form = this.forms[ index ],
			languageFields = form.$$( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATIONS ),
			representationValues = [];

		_.each( languageFields, function ( element, key ) {
			representationValues.push( {
				value: languageFields[ key ].$( '.representation-widget_representation-value' ).getText(),
				language: languageFields[ key ].$( '.representation-widget_representation-language' ).getText()
			} );
		} );

		return {
			representations: representationValues
		};
	}

	getNthFormStatement( index ) {
		const form = this.forms[ index ];

		form.$( '.wikibase-snakview-body .wikibase-snakview-variation-valuesnak  .valueview-instaticmode' ).waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		form.$( '.wikibase-statementgroupview-property-label a' ).waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

		const property = form.$( '.wikibase-statementgroupview' ),
			value = form.$( '.wikibase-snakview-body .wikibase-snakview-variation-valuesnak  .valueview-instaticmode' );

		return {
			propertyId: property.getAttribute( 'id' ).split( '-' ), value: value.getText()
		};
	}

	addStatementToNthForm( index, statementPropertyId, statementValue, submitImmediately ) {
		const form = this.forms[ index ],
			addStatementLink = form.$( this.constructor.FORM_WIDGET_SELECTORS.ADD_STATEMENT_TO_FORM );

		addStatementLink.click();

		const propertyInputfield = form.$( '.wikibase-statementgroupview .wikibase-snakview-property input' );

		propertyInputfield.setValue( statementPropertyId );
		this.selectFirstSuggestedEntityOnEntitySelector();
		this.formClaimValueInputField.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		this.formClaimValueInputField.setValue( statementValue );

		if ( submitImmediately !== false ) {
			this.submitNthFormStatement( index );
		}
	}

	submitNthFormStatement( index ) {
		const form = this.forms[ index ],
			saveLink = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		browser.waitUntil( () => {
			return saveLink.getAttribute( 'aria-disabled' ) === 'false';
		} );

		saveLink.click();
		saveLink.waitForExist( { reverse: true } );
	}

	addRepresentationToNthForm( index, representation, language, submitImmediately ) {
		const form = this.forms[ index ];

		this.startEditingNthForm( index );

		const addRepresentationButton = form.$( this.constructor.FORM_WIDGET_SELECTORS.ADD_REPRESENTATION_BUTTON );

		addRepresentationButton.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		addRepresentationButton.click();

		const representationContainer = form.$( '.representation-widget_representation-list' );
		const representations = representationContainer.$$( '.representation-widget_representation-edit-box' );

		const newRepresentationIndex = representations.length - 1;
		const newRepresentation = representations[ newRepresentationIndex ];

		newRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( representation );
		newRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	restorePreviousRevision() {
		this.viewHistoryLink.click();
		browser.pause( 1000 );
		this.restoreRevisionLink.click();
		browser.pause( 1000 );
		this.undoOrRestoreSavePageButton.click();
		this.addFormLink.waitForDisplayed();
	}

	undoLatestRevision() {
		this.viewHistoryLink.click();
		browser.pause( 1000 );
		this.undoRevisionLink.click();
		browser.pause( 1000 );
		this.undoOrRestoreSavePageButton.click();
		this.addFormLink.waitForDisplayed();
	}

	editRepresentationOfNthForm( index, representation, language, submitImmediately ) {
		const form = this.forms[ index ];

		this.startEditingNthForm( index );

		form.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( representation );
		form.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	removeLastRepresentationOfNthForm( index, submitImmediately ) {
		const form = this.forms[ index ];

		this.startEditingNthForm( index );

		const representationContainer = form.$( '.representation-widget_representation-list' );
		const representations = representationContainer.$$( '.representation-widget_representation-edit-box' );

		const lastRepresentationIndex = representations.length - 1;
		const lastRepresentation = representations[ lastRepresentationIndex ];

		lastRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.REMOVE_REPRESENTATION_BUTTON ).click();

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	startEditingNthForm( index ) {
		this.forms[ index ].waitForClickable( { timeout: browser.config.nonApiTimeout } );
		this.forms[ index ].$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON ).click();
	}

	isNthFormSubmittable( index ) {
		const form = this.forms[ index ],
			saveButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		return saveButton.getAttribute( 'aria-disabled' ) !== 'true';
	}

	submitNthForm( index ) {
		const form = this.forms[ index ];

		const saveButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		saveButton.waitForClickable( { timeout: browser.config.nonApiTimeout } );
		saveButton.click();
		saveButton.waitForExist( { reverse: true } );
	}

	addGrammaticalFeatureToNthForm( index, grammaticalFeatureId, submitImmediately ) {
		const form = this.forms[ index ];
		const editButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON );

		editButton.waitForClickable( { timeout: browser.config.nonApiTimeout } );
		editButton.click();

		this.setSingleValueOnMultiselectElement(
			form.$( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES ),
			grammaticalFeatureId
		);

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	removeGrammaticalFeatureFromNthForm( index, submitImmediately ) {
		const form = this.forms[ index ];

		this.startEditingNthForm( index );
		const gramFeaturesValues = form.$( '.wikibase-lexeme-form-grammatical-features-values' );
		const gramFeatureToDelete = gramFeaturesValues.$$( '.oo-ui-tagItemWidget' );
		const gramFeatureRemove = gramFeatureToDelete[ 0 ].$( '.oo-ui-buttonElement-button' );

		gramFeatureRemove.click();

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	getFormAnchor( index ) {
		const form = this.forms[ index ];

		return form.getAttribute( 'id' );
	}

	isUserLoggedIn() {
		$( this.constructor.PERSONAL_BAR.USER_TOOLBAR ).waitForExist( { reverse: true, timeout: browser.config.nonApiTimeout } );
		return !$( this.constructor.PERSONAL_BAR.USER_NOT_LOGIN_ICON ).isExisting();
	}

}

module.exports = new LexemePage();

'use strict';

const Page = require( '../../../../../tests/selenium/pageobjects/page' );

class LexemePage extends Page {

	static get LEMMA_WIDGET_SELECTORS() {
		return {
			EDIT_BUTTON: '.lemma-widget_edit',
			SAVE_BUTTON: '.lemma-widget_save',
			EDIT_INPUT_VALUE: '.lemma-widget_lemma-value-input',
			EDIT_INPUT_LANGUAGE: '.lemma-widget_lemma-language-input'
		};
	}

	static get GLOSS_WIDGET_SELECTORS() {
		return {
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove',
			SAVE_BUTTON: '.wikibase-toolbar-button-save'
		};
	}

	static get FORM_WIDGET_SELECTORS() {
		return {
			REPRESENTATION_VALUE: '.wikibase-lexeme-form-header .representation-widget_representation-value',
			REPRESENTATION_LANGUAGE: '.wikibase-lexeme-form-header .representation-widget_representation-language',
			EDIT_INPUT_VALUE: '.representation-widget_representation-value-input',
			EDIT_INPUT_LANGUAGE: '.representation-widget_representation-language-input',
			GRAMMATICAL_FEATURES: '.wikibase-lexeme-form-grammatical-features-values',
			ADD_REPRESENTATION_BUTTON: '.representation-widget_add',
			REMOVE_REPRESENTATION_BUTTON: '.representation-widget_representation-remove'
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

	get headerId() {
		return $( '.wb-lexeme-header_id' ).getText();
	}

	get addFormLink() {
		return $( '.wikibase-lexeme-forms-section > .wikibase-addtoolbar .wikibase-toolbar-button-add a' );
	}

	/**
	 * Open the given Lexeme page
	 *
	 * @param {string} lexemeId
	 */
	open( lexemeId ) {
		super.open( 'Lexeme:' + lexemeId );
	}

	/**
	 * @param {string} lemmaText
	 * @param {string} languageCode
	 */
	setFirstLemma( lemmaText, languageCode ) {
		browser.waitForVisible( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON );
		let editButton = $( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON );
		editButton.click();

		let lemma = this.lemmas[ 0 ];

		lemma.$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( lemmaText );
		lemma.$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( languageCode );

		let saveButton = $( this.constructor.LEMMA_WIDGET_SELECTORS.SAVE_BUTTON );

		saveButton.click();
		saveButton.waitForExist( null, true );
	}

	/**
	 * Add a form
	 *
	 * @param {string} value
	 * @param {string} language
	 */
	addForm( value, language ) {
		this.addFormLink.waitForVisible();
		this.addFormLink.click();

		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );
		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		this.formsContainer.$( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON ).click();

		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).waitForExist( null, true );
	}

	/**
	 * Remove the nth for on the page
	 *
	 * @param {int} index
	 */
	removeNthForm( index ) {
		let form = this.forms[ index ];

		form.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_BUTTON ).click();

		let removeButton = form.$( this.constructor.GLOSS_WIDGET_SELECTORS.REMOVE_BUTTON );

		removeButton.waitForVisible();
		removeButton.click();
		removeButton.waitForExist( null, true );
	}

	/**
	 * Get data of the nth form on the page
	 *
	 * @param {int} index
	 * @return {{value, language}}
	 */
	getNthFormData( index ) {
		let form = this.forms[ index ];

		return {
			value: form.$( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_VALUE ).getText(),
			language: form.$( this.constructor.FORM_WIDGET_SELECTORS.REPRESENTATION_LANGUAGE ).getText(),
			grammaticalFeatures: form.$( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES ).getText()
		};
	}

	addRepresentationToNthForm( index, representation, language ) {
		let form = this.forms[ index ];

		form.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_BUTTON ).click();

		let addRepresentationButton = form.$( this.constructor.FORM_WIDGET_SELECTORS.ADD_REPRESENTATION_BUTTON );

		addRepresentationButton.waitForVisible();
		addRepresentationButton.click();

		let representationContainer = form.$( '.representation-widget_representation-list' );
		let representations = representationContainer.$$( '.representation-widget_representation-edit-box' );

		let newRepresentationIndex = representations.length - 1;
		let newRepresentation = representations[ newRepresentationIndex ];

		newRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( representation );
		newRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		let saveButton = form.$( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON );

		saveButton.click();
		saveButton.waitForExist( null, true );
	}

	editRepresentationOfNthForm( index, representation, language ) {
		let form = this.forms[ index ];

		form.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_BUTTON ).click();

		form.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( representation );
		form.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		let saveButton = form.$( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON );

		saveButton.click();
		saveButton.waitForExist( null, true );
	}

	removeLastRepresentationOfNthForm( index ) {
		let form = this.forms[ index ];

		form.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_BUTTON ).click();

		let representationContainer = form.$( '.representation-widget_representation-list' );
		let representations = representationContainer.$$( '.representation-widget_representation-edit-box' );

		let lastRepresentationIndex = representations.length - 1;
		let lastRepresentation = representations[ lastRepresentationIndex ];

		lastRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.REMOVE_REPRESENTATION_BUTTON ).click();

		let saveButton = form.$( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON );

		saveButton.click();
		saveButton.waitForExist( null, true );
	}

}

module.exports = new LexemePage();

'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	_ = require( 'lodash' );

let MixinBuilder, MainStatementSection, ComponentInteraction;
try {
	MixinBuilder = require( 'wdio-wikibase/pagesections/mixinbuilder' );
	MainStatementSection = require( 'wdio-wikibase/pagesections/main.statement.section' );
	ComponentInteraction = require( 'wdio-wikibase/pagesections/ComponentInteraction' );
} catch ( e ) {
	MixinBuilder = require( '../../../../Wikibase/repo/tests/selenium/wdio-wikibase/pagesections/mixinbuilder' );
	MainStatementSection = require( '../../../../Wikibase/repo/tests/selenium/wdio-wikibase/pagesections/main.statement.section' );
	ComponentInteraction = require( '../../../../Wikibase/repo/tests/selenium/wdio-wikibase/pagesections/ComponentInteraction' );
}

class LexemePage extends MixinBuilder.mix( Page ).with( MainStatementSection, ComponentInteraction ) {

	static get LEMMA_WIDGET_SELECTORS() {
		return {
			EDIT_BUTTON: '.lemma-widget_edit',
			SAVE_BUTTON: '.lemma-widget_save',
			ADD_BUTTON: '.lemma-widget_add',
			EDIT_INPUT_VALUE: '.lemma-widget_lemma-value-input',
			EDIT_INPUT_LANGUAGE: '.lemma-widget_lemma-language-input'
		};
	}

	static get GLOSS_WIDGET_SELECTORS() {
		return {
			ADD_GLOSS_BUTTON: '.wikibase-lexeme-sense-glosses-add',
			EDIT_INPUT_VALUE: '.wikibase-lexeme-sense-gloss-value-input',
			EDIT_INPUT_LANGUAGE: '.wikibase-lexeme-sense-gloss-language-input'
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

	static get GENERIC_TOOLBAR_SELECTORS() {
		return {
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove',
			SAVE_BUTTON: '.wikibase-toolbar-button-save'
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

	get sensesContainer() {
		return $( '.wikibase-lexeme-senses' );
	}

	get forms() {
		return this.formsContainer.$$( '.wikibase-lexeme-form' );
	}

	get senses() {
		return this.sensesContainer.$$( '.wikibase-lexeme-sense' );
	}

	get headerId() {
		return $( '.wb-lexeme-header_id' ).getText().replace( /[^L0-9]/g, '' ); // remove non-marked-up styling text "(L123)"
	}

	get addFormLink() {
		return $( '.wikibase-lexeme-forms-section > .wikibase-addtoolbar .wikibase-toolbar-button-add a' );
	}

	get headerSaveButton() {
		return $( this.constructor.LEMMA_WIDGET_SELECTORS.SAVE_BUTTON );
	}

	/**
	 * Open the given Lexeme page
	 *
	 * @param {string} lexemeId
	 */
	open( lexemeId ) {
		super.openTitle( 'Lexeme:' + lexemeId );
		browser.waitForVisible( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON );
		this.addFormLink.waitForVisible(); // last button on page, probably the last
	}

	/**
	 * @param {string} lemmaText
	 * @param {string} languageCode
	 */
	setFirstLemma( lemmaText, languageCode ) {
		this.startHeaderEditMode();

		this.fillNthLemma( 0, lemmaText, languageCode );

		this.headerSaveButton.click();
		this.headerSaveButton.waitForExist( null, true );
	}

	startHeaderEditMode() {
		$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_BUTTON ).click();
	}

	fillNthLemma( position, lemmaText, languageCode ) {
		for ( let i = this.lemmas.length; i <= position; i++ ) {
			$( this.constructor.LEMMA_WIDGET_SELECTORS.ADD_BUTTON ).click();
		}

		let lemma = this.lemmas[ position ];
		lemma.$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( lemmaText );
		lemma.$( this.constructor.LEMMA_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( languageCode );
	}

	isHeaderSubmittable() {
		return this.headerSaveButton.getAttribute( 'disabled' ) !== 'true';
	}

	/**
	 * Add a form
	 *
	 * @param {string} value
	 * @param {string} language
	 */
	addForm( value, language ) {
		this.addFormLink.click();

		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );
		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		this.formsContainer.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON ).click();

		this.formsContainer.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).waitForExist( null, true );
	}

	/**
	 * Remove the nth for on the page
	 *
	 * @param {int} index
	 */
	removeNthForm( index ) {
		let form = this.forms[ index ];

		form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON ).click();

		let removeButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.REMOVE_BUTTON );

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

	/**
	 * Get data of the nth form on the page
	 *
	 * @param {int} index
	 * @return {{value, language}}
	 */
	getNthFormFormValues( index ) {
		let form = this.forms[ index ],
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

	getNthSenseFormValues( index ) {
		let sense = this.senses[ index ],
			languageFields = sense.$$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ),
			glossInputs = [];

		_.each( sense.$$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE ), function ( element, key ) {
			glossInputs.push( {
				value: element.getValue(),
				language: languageFields[ key ].getValue()
			} );
		} );

		return {
			glosses: glossInputs
		};
	}

	addRepresentationToNthForm( index, representation, language, submitImmediately ) {
		let form = this.forms[ index ];

		this.startEditingNthForm( index );

		let addRepresentationButton = form.$( this.constructor.FORM_WIDGET_SELECTORS.ADD_REPRESENTATION_BUTTON );

		addRepresentationButton.waitForVisible();
		addRepresentationButton.click();

		let representationContainer = form.$( '.representation-widget_representation-list' );
		let representations = representationContainer.$$( '.representation-widget_representation-edit-box' );

		let newRepresentationIndex = representations.length - 1;
		let newRepresentation = representations[ newRepresentationIndex ];

		newRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( representation );
		newRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	addGlossToNthSense( index, gloss, language, submitImmediately ) {
		let sense = this.senses[ index ];

		this.startEditingNthSense( index );

		let addGlossButton = sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.ADD_GLOSS_BUTTON );

		addGlossButton.waitForVisible();
		addGlossButton.click();

		let glossContainer = sense.$( '.wikibase-lexeme-sense-glosses-table' );
		let glosses = glossContainer.$$( '.wikibase-lexeme-sense-gloss' );

		let newGlossIndex = glosses.length - 1;
		let newGloss = glosses[ newGlossIndex ];

		newGloss.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );
		newGloss.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( gloss );

		if ( submitImmediately !== false ) {
			this.submitNthSense( index );
		}
	}

	editRepresentationOfNthForm( index, representation, language, submitImmediately ) {
		let form = this.forms[ index ];

		this.startEditingNthForm( index );

		form.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( representation );
		form.$( this.constructor.FORM_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	removeLastRepresentationOfNthForm( index, submitImmediately ) {
		let form = this.forms[ index ];

		this.startEditingNthForm( index );

		let representationContainer = form.$( '.representation-widget_representation-list' );
		let representations = representationContainer.$$( '.representation-widget_representation-edit-box' );

		let lastRepresentationIndex = representations.length - 1;
		let lastRepresentation = representations[ lastRepresentationIndex ];

		lastRepresentation.$( this.constructor.FORM_WIDGET_SELECTORS.REMOVE_REPRESENTATION_BUTTON ).click();

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}

	startEditingNthForm( index ) {
		this.forms[ index ].$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON ).click();
	}

	startEditingNthSense( index ) {
		this.senses[ index ].$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON ).click();
	}

	isNthFormSubmittable( index ) {
		let form = this.forms[ index ],
			saveButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		return saveButton.getAttribute( 'aria-disabled' ) !== 'true';
	}

	isNthSenseSubmittable( index ) {
		let sense = this.senses[ index ],
			saveButton = sense.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		return saveButton.getAttribute( 'aria-disabled' ) !== 'true';
	}

	submitNthForm( index ) {
		let form = this.forms[ index ];

		let saveButton = form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		saveButton.click();
		saveButton.waitForExist( null, true );
	}

	submitNthSense( index ) {
		let sense = this.senses[ index ];

		let saveButton = sense.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.SAVE_BUTTON );

		saveButton.click();
		saveButton.waitForExist( null, true );
	}

	addGrammaticalFeatureToNthForm( index, grammaticalFeatureId, submitImmediately ) {
		let form = this.forms[ index ];

		form.$( this.constructor.GENERIC_TOOLBAR_SELECTORS.EDIT_BUTTON ).click();

		this.setValueOnLookupElement(
			form.$( this.constructor.FORM_WIDGET_SELECTORS.GRAMMATICAL_FEATURES ),
			grammaticalFeatureId
		);

		if ( submitImmediately !== false ) {
			this.submitNthForm( index );
		}
	}
	isUserLoggedIn() {
		$( this.constructor.PERSONAL_BAR.USER_TOOLBAR ).waitForExist( null, false );
		return !$( this.constructor.PERSONAL_BAR.USER_NOT_LOGIN_ICON ).isExisting();
	}

}

module.exports = new LexemePage();

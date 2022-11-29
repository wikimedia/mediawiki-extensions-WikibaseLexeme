'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	_ = require( 'lodash' );
class SensePage extends Page {

	static get GLOSS_WIDGET_SELECTORS() {
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
			SENSE_VALUE: '.wikibase-lexeme-sense-gloss > .wikibase-lexeme-sense-gloss-value-cell > span',
			SENSE_LANGUAGE: '.wikibase-lexeme-sense-gloss-language',
			SENSE_ID: '.wikibase-lexeme-sense-id'
		};
	}

	get sensesContainer() {
		return $( '.wikibase-lexeme-senses' );
	}

	get addSenseLink() {
		return $( '.wikibase-lexeme-senses-section > .wikibase-addtoolbar .wikibase-toolbar-button-add a' );
	}

	get senses() {
		return this.sensesContainer.$$( '.wikibase-lexeme-sense' );
	}

	get sensesHeader() {
		return $( '.wikibase-lexeme-senses-section h2#senses' ).getText();
	}

	get senseId() {
		return $( '.wikibase-lexeme-sense-id' ).getText();
	}

	startEditingNthSense( index ) {
		this.senses[ index ].$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_BUTTON ).click();
	}

	/**
	 * Add a sense
	 *
	 * @param {string} language
	 * @param {string} value
	 */
	addSense( language, value ) {
		this.addSenseLink.click();

		this.sensesContainer.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );
		this.sensesContainer.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );

		this.sensesContainer.$( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON ).click();
		this.waitUntilStateChangeIsDone();

	}

	editSenseNoSubmit( index, value ) {
		this.startEditingNthSense( index );
		this.sensesContainer.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );
	}

	editSensValueAndSubmit( index, value ) {
		this.startEditingNthSense( index );
		this.sensesContainer.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );
		this.submitNthSense( index );
		this.waitUntilStateChangeIsDone();
	}

	/**
	 * Get data of the nth Sense on the page
	 *
	 * @param {number} index
	 * @return {{value, language, senseIdElement}}
	 */
	getNthSenseData( index ) {
		const sense = this.senses[ index ];

		sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_VALUE ).waitForExist( { timeout: browser.config.nonApiTimeout } );

		return {
			value: sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_VALUE ).getText(),
			language: sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_LANGUAGE ).getText(),
			senseIdElement: sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_ID )
		};
	}

	getNthSenseFormValues( index ) {
		const sense = this.senses[ index ],
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

	addGlossToNthSense( index, gloss, language, submitImmediately ) {
		const sense = this.senses[ index ];

		this.startEditingNthSense( index );

		const addGlossButton = sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.ADD_GLOSS_BUTTON );

		addGlossButton.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );
		addGlossButton.click();

		const glossContainer = sense.$( '.wikibase-lexeme-sense-glosses-table' );
		const glosses = glossContainer.$$( '.wikibase-lexeme-sense-gloss' );

		const newGlossIndex = glosses.length - 1;
		const newGloss = glosses[ newGlossIndex ];

		newGloss.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_LANGUAGE ).setValue( language );
		newGloss.$( this.constructor.GLOSS_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( gloss );

		if ( submitImmediately !== false ) {
			this.submitNthSense( index );
		}
	}

	isNthSenseSubmittable( index ) {
		const sense = this.senses[ index ],
			saveButton = sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON );

		return saveButton.getAttribute( 'aria-disabled' ) !== 'true';
	}

	submitNthSense( index ) {
		browser.waitUntil( () => {
			return this.isNthSenseSubmittable( index );
		} );

		const sense = this.senses[ index ];
		const saveButton = sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.SAVE_BUTTON );
		saveButton.waitForClickable( { timeout: browser.config.nonApiTimeout } );
		saveButton.click();
		saveButton.waitForExist( { reverse: true } );
	}

	waitUntilStateChangeIsDone() {
		$( this.constructor.GLOSS_WIDGET_SELECTORS.CHANGESTATE_INDICATOR )
			.waitForExist( { reverse: true } );
	}

	doesSenseExist() {
		return this.senses.length > 0;
	}

	getSenseAnchor( index ) {
		const sense = this.senses[ index ];

		return sense.getAttribute( 'id' );
	}

	removeSense( index ) {
		const sense = this.senses[ index ];
		const removeButton = sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.SENSE_REMOVE_BUTTON );
		removeButton.click();
	}

	removeGloss( index, submitImmediately ) {
		const sense = this.senses[ index ];
		const glossContainer = sense.$( '.wikibase-lexeme-sense-glosses-table' );
		const glosses = glossContainer.$$( '.wikibase-lexeme-sense-gloss' );

		const removeButton = glosses[ 1 ].$( this.constructor.GLOSS_WIDGET_SELECTORS.GLOSS_REMOVE_BUTTON );
		removeButton.click();

		if ( submitImmediately !== false ) {
			this.submitNthSense( index );
		}
	}

	cancelSenseEditing( index ) {
		const sense = this.senses[ index ];

		const cancelButton = sense.$( this.constructor.GLOSS_WIDGET_SELECTORS.CANCEL_BUTTON );
		cancelButton.click();
	}
}

module.exports = new SensePage();

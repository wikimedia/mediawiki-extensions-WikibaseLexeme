'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	WikibaseApi = require( '../../../../Wikibase/repo/tests/selenium/wikibase.api' );

describe( 'Lexeme:Forms', () => {

	it( 'can be added', () => {
		let id;

		/**
		 * This block is a hotfix for the integration issue with ContentTranslation
		 * documented here:  https://phabricator.wikimedia.org/T200693
		 *
		 * Creating a dummy item and "handling" the failure is a temporary
		 * workaround to the problem of not being able to create the first item.
		 */
		browser.call( () => {
			return WikibaseApi.createItem()
				.then( () => {
					// do nothing
				} )
				.catch( () => {
					// do nothing
				} );
		} );

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.addForm( 'Yacht', 'de' );

		let form = LexemePage.getNthFormData( 0 );

		assert.equal( 'Yacht', form.value, 'Form added to GUI shows value' );
		assert.equal( 'de', form.language, 'Form added to GUI shows language' );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( 1, lexeme.forms.length, 'Form to be found via API' );
					assert.equal( 'Yacht', lexeme.forms[ 0 ].representations.de.value, 'Correct form in API' );
				} );
		} );
	} );

} );

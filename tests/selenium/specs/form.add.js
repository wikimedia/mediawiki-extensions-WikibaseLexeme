'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

describe( 'Lexeme:Forms', () => {

	before( 'check logged in', () => {
		LoginPage.open();
		if ( !LexemePage.isUserLoggedIn() ) {
			LoginPage.loginAdmin();
		}
	} );

	it( 'can be added', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		LexemePage.addForm( 'Yacht', 'de' );

		let form = LexemePage.getNthFormData( 0 );

		assert.equal( 'Yacht', form.value, 'Form added to GUI shows value' );
		assert.equal( 'de', form.language, 'Form added to GUI shows language' );

		browser.call( () => LexemeApi.get( id ).then( ( lexeme ) => {
			assert.equal( 1, lexeme.forms.length, 'Form to be found via API' );
			assert.equal( 'Yacht', lexeme.forms[ 0 ].representations.de.value, 'Correct form in API' );
		} ) );
	} );

} );

'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	SensePage = require( '../pageobjects/sense.page' );

describe( 'Lexeme:Senses', () => {

	before( 'check logged in, create lexeme and sense', () => {
		browser.deleteAllCookies();
		LoginPage.loginAdmin();

		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );
		SensePage.addSense( 'en', 'Yacht' );
	} );

	it( 'Sense header and container exist', () => {
		const header = 'Senses';

		assert.strictEqual( SensePage.sensesHeader, header );
		assert( SensePage.sensesContainer.isExisting() );
	} );
} );

'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	SensePage = require( '../pageobjects/sense.page' );

describe( 'Lexeme:Senses', () => {

	before( 'check logged in, create lexeme and sense', () => {
		LoginPage.open();
		if ( !LexemePage.isUserLoggedIn() ) {
			LoginPage.loginAdmin();
		}
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );

		} );
		LexemePage.open( id );
		SensePage.addSense( 'en', 'Yacht' );
	} );

	it( 'Adding Sense', () => {
		let sense = SensePage.getNthSenseData( 0 );

		assert.strictEqual( 'English', sense.language, 'Sense added to GUI shows language' );
		assert.strictEqual( 'Yacht', sense.value, 'Sense added to GUI shows value' );
		assert( sense.senseIdElement.isExisting() );
	} );

	it( 'Added Sense has statement', () => {
		let senseStatements = SensePage.senseStatements;
		let senseId = SensePage.senseId;

		assert.strictEqual( senseStatements, 'Statements about ' + senseId );
	} );

	it( 'Anchor exists and is equal to Sense ID', () => {
		let senseId = SensePage.senseId.split( '-' )[ 1 ];
		let anchorId = SensePage.getSenseAnchor( 0 );

		assert.strictEqual( senseId, anchorId );
	} );
} );

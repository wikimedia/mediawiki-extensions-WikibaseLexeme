'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	loginAdmin = require( '../loginAdmin' );

describe( 'Lexeme:Header', () => {

	beforeEach( 'check logged in', () => {
		browser.deleteAllCookies();
		loginAdmin();
	} );

	it( 'shows id', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		assert.equal( LexemePage.headerId, id );
	} );

} );

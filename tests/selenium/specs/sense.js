'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	SensePage = require( '../pageobjects/sense.page' );

describe( 'Lexeme:Senses', () => {

	before( 'create lexeme and sense', () => {
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

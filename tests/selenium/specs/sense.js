'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	Replication = require( '../replication' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	SensePage = require( '../pageobjects/sense.page' );

describe( 'Lexeme:Senses', () => {

	it( 'Sense header and container exist', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );
		Replication.waitForReplicationLag( LexemeApi.getBot() );

		LexemePage.open( id );
		SensePage.addSense( 'en', 'Yacht' );

		const header = 'Senses';

		assert.strictEqual( SensePage.sensesHeader, header );
		assert( SensePage.sensesContainer.isExisting() );
	} );
} );

'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	Replication = require( '../replication' );

let WikibaseApi;
try {
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
} catch ( e ) {
	WikibaseApi = require( '../../../../Wikibase/repo/tests/selenium/wdio-wikibase/wikibase.api' );
}

describe( 'Lexeme:Header', () => {

	it( 'can edit the language of a Lexeme', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );
		const languageItem = browser.call( () => WikibaseApi.createItem() );

		LexemePage.open( id );
		LexemePage.startHeaderEditMode();

		LexemePage.setLexemeLanguageItem( languageItem );

		Replication.waitForReplicationLag( LexemeApi.getBot() );
		browser.call( () => LexemeApi.get( id )
			.then( ( lexeme ) => {
				assert.equal( languageItem, lexeme.language, 'Unexpected Language value' );
			} ).catch( assert.fail )
		);

	} );

	it.skip( 'can edit the lexical category of a Lexeme', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );
		const categoryItem = browser.call( () => WikibaseApi.createItem() );

		LexemePage.open( id );
		LexemePage.startHeaderEditMode();

		LexemePage.setLexicalCategoryItem( categoryItem );

		Replication.waitForReplicationLag( LexemeApi.getBot() );
		browser.call( () => LexemeApi.get( id )
			.then( ( lexeme ) => {
				assert.equal( categoryItem, lexeme.lexicalCategory, 'Unexpected lexical category value' );
			} ).catch( assert.fail )
		);

	} );
} );

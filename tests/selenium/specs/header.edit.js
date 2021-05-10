'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

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

		// Wait for 120% of the current replication lag to increase the chance we
		// get up to date entity data below, even if reading from a replica.
		browser.call( () => LexemeApi.getReplicationLag()
			.then( ( replag ) => browser.pause( replag * 1200 ) )
		);
		browser.call( () => LexemeApi.get( id )
			.then( ( lexeme ) => {
				assert.equal( languageItem, lexeme.language, 'Unexpected Language value' );
			} ).catch( assert.fail )
		);

	} );

	it( 'can edit the lexical category of a Lexeme', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );
		const categoryItem = browser.call( () => WikibaseApi.createItem() );

		LexemePage.open( id );
		LexemePage.startHeaderEditMode();

		LexemePage.setLexicalCategoryItem( categoryItem );

		// Wait for 120% of the current replication lag to increase the chance we
		// get up to date entity data below, even if reading from a replica.
		browser.call( () => LexemeApi.getReplicationLag()
			.then( ( replag ) => browser.pause( replag * 1200 ) )
		);
		browser.call( () => LexemeApi.get( id )
			.then( ( lexeme ) => {
				assert.equal( categoryItem, lexeme.lexicalCategory, 'Unexpected lexical category value' );
			} ).catch( assert.fail )
		);

	} );
} );

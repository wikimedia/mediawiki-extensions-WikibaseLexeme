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
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} ).catch( assert.fail );
		} );

		LexemePage.open( id );
		LexemePage.startHeaderEditMode();

		let languageItem;
		browser.call( () => {
			return WikibaseApi.createItem()
				.then( ( item ) => {
					languageItem = item;
				} ).catch( assert.fail );
		} );

		LexemePage.setLexemeLanguageItem( languageItem );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( languageItem, lexeme.language, 'Unexpected Language value' );
				} ).catch( assert.fail );
		} );

	} );

	it( 'can edit the lexical category of a Lexeme', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} ).catch( assert.fail );
		} );

		LexemePage.open( id );
		LexemePage.startHeaderEditMode();

		let categoryItem;
		browser.call( () => {
			return WikibaseApi.createItem()
				.then( ( item ) => {
					categoryItem = item;
				} ).catch( assert.fail );
		} );

		LexemePage.setLexicalCategoryItem( categoryItem );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( categoryItem, lexeme.lexicalCategory, 'Unexpected lexical category value' );
				} ).catch( assert.fail );
		} );

	} );
} );

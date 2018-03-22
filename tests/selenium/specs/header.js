'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

describe( 'Lexeme:Header', () => {

	it( 'shows id', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		assert.equal( LexemePage.headerId, '(' + id + ')' );
	} );

} );

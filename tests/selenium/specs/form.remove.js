'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

describe( 'Lexeme:Forms', () => {

	it( 'can be removed', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} )
				.then( () => {
					return LexemeApi.addForm(
						id,
						{
							representations: {
								de: { language: 'de', value: 'lorem' }
							},
							grammaticalFeatures: []
						}
					);
				} );
		} );

		LexemePage.open( id );

		LexemePage.removeNthForm( 0 );

		assert.equal( 0, LexemePage.forms.length, 'form removed from GUI' );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( 0, lexeme.forms.length, 'no forms to be found via API' );
				} );
		} );

	} );

} );

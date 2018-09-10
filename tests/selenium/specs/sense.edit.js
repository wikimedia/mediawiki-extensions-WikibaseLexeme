'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

describe( 'Lexeme:Senses', () => {

	it( 'can not save senses with redundant languages', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} )
				.then( () => {
					return LexemeApi.addSense(
						id,
						{
							glosses: {
								en: { language: 'en', value: 'one' }
							}
						}
					);
				} );
		} );

		LexemePage.open( id );

		LexemePage.addGlossToNthSense( 0, 'two', 'en', false );

		assert.equal( LexemePage.isNthSenseSubmittable( 0 ), false );
	} );
} );

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

	it( 'shows the language and language code in edit mode', () => {
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
								en: { language: 'en', value: 'goat' }
							}
						}
					);
				} );
		} );

		LexemePage.open( id );
		LexemePage.startEditingNthSense( 0 );
		var senseValues = LexemePage.getNthSenseFormValues( 0 );
		assert.equal( senseValues.glosses[ 0 ].language, 'English (en)' );
	} );
} );

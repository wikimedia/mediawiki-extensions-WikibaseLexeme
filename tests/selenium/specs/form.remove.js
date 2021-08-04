'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

describe( 'Lexeme:Forms', () => {

	it( 'can be removed', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const lexemeId = lexeme.id;
			return LexemeApi.addForm(
				lexemeId,
				{
					representations: {
						de: { language: 'de', value: 'lorem' }
					},
					grammaticalFeatures: []
				}
			).then( () => lexemeId );
		} ) );

		LexemePage.open( id );

		LexemePage.removeNthForm( 0 );

		assert.equal( 0, LexemePage.forms.length, 'form removed from GUI' );

		browser.call( () => LexemeApi.get( id ).then( ( lexeme ) => {
			assert.equal( 0, lexeme.forms.length, 'no forms to be found via API' );
		} ) );

	} );

} );

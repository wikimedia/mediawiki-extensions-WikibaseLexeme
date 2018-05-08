'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

describe( 'Lexeme:Forms', () => {

	it( 'can add representation', () => {
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
								'en-ca': { language: 'en-ca', value: 'color' }
							},
							grammaticalFeatures: []
						}
					);
				} );
		} );

		LexemePage.open( id );

		LexemePage.addRepresentationToNthForm( 0, 'colour', 'en-gb' );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( 'color', lexeme.forms[ 0 ].representations[ 'en-ca' ].value, 'Old representation not changed' );
					assert.equal( 'colour', lexeme.forms[ 0 ].representations[ 'en-gb' ].value, 'New representation added' );
				} );
		} );

	} );

	it( 'can edit representation', () => {
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
								en: { language: 'en', value: 'color' }
							},
							grammaticalFeatures: []
						}
					);
				} );
		} );

		LexemePage.open( id );

		LexemePage.editRepresentationOfNthForm( 0, 'colour', 'en' );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( 'colour', lexeme.forms[ 0 ].representations.en.value, 'Representation changed' );
				} );
		} );

	} );

	it( 'can remove representation', () => {
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
								'en-ca': { language: 'en-ca', value: 'color' },
								'en-gb': { language: 'en-gb', value: 'colour' }
							},
							grammaticalFeatures: []
						}
					);
				} );
		} );

		LexemePage.open( id );

		LexemePage.removeLastRepresentationOfNthForm( 0 );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.ok( 'en-gb' in lexeme.forms[ 0 ].representations === false, 'Representation removed' );
				} );
		} );

	} );

} );

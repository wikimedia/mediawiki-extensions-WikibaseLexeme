'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	WikibaseApi = require( '../wikibase.api' );

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

	it( 'can prefill representation language for lexeme with one lemma', () => {
		let id,
			formValues;

		browser.call( () => {
			return LexemeApi.create() // adds lexeme with on 'en' lemma
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.addFormLink.click();
		formValues = LexemePage.getNthFormFormValues( 0 );

		assert.equal( 'en', formValues.representations[ 0 ].language, 'Representation has default language from lemma' );
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

	it( 'can not save representations with redundant languages', () => {
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

		LexemePage.addRepresentationToNthForm( 0, 'colour', 'en', false );

		assert.equal( LexemePage.isNthFormSubmittable( 0 ), false );
	} );

	it( 'can add grammatical feature', () => {
		let id,
			grammaticalFeatureId;

		browser.call( () => {
			return WikibaseApi.createItem()
				.then( ( itemId ) => {
					grammaticalFeatureId = itemId;
				} );
		} );

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

		LexemePage.addGrammaticalFeatureToNthForm( 0, grammaticalFeatureId );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.deepEqual( [ grammaticalFeatureId ], lexeme.forms[ 0 ].grammaticalFeatures );
				} );
		} );

	} );
} );

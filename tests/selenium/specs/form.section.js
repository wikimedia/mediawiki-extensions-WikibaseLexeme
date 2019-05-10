'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

describe( 'Form:Header', () => {

	beforeEach( 'check logged in', () => {
		LoginPage.open();
		if ( !LexemePage.isUserLoggedIn() ) {
			LoginPage.loginAdmin();
		}
	} );

	it( 'shows Forms header', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		assert( LexemePage.hasFormHeader );
	} );

	it( 'has Forms container', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		assert( LexemePage.formsContainer.isExisting() );
	} );

	it( 'has an ID', () => {
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

		assert( LexemePage.formId.isExisting() );
	} );

	it( 'has representation', () => {
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

		assert( LexemePage.hasRepresentation );
	} );

	it( 'has each representation having a language', () => {
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

		let form = LexemePage.getNthFormData( 0 );

		assert.equal( 'en', form.language, 'Form added to GUI shows language' );
	} );

	it( 'show Forms grammatical features', () => {
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
		LexemePage.addFormLink.click();

		assert( LexemePage.hasGramaticalFeatureList );
	} );

} );

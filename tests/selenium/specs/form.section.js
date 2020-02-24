'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

describe( 'Form:Header', () => {

	before( 'check logged in', () => {
		browser.deleteAllCookies();
		LoginPage.loginAdmin();
	} );

	it( 'shows Forms header', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		assert( LexemePage.hasFormHeader );
	} );

	it( 'has Forms container', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		assert( LexemePage.formsContainer.isExisting() );
	} );

	it( 'has an ID', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addForm(
				id,
				{
					representations: {
						'en-ca': { language: 'en-ca', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );

		assert( LexemePage.formId.isExisting() );
	} );

	it( 'has representation', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addForm(
				id,
				{
					representations: {
						'en-ca': { language: 'en-ca', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );

		assert( LexemePage.hasRepresentation );
	} );

	it( 'has each representation having a language', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addForm(
				id,
				{
					representations: {
						en: { language: 'en', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );

		let form = LexemePage.getNthFormData( 0 );

		assert.equal( 'en', form.language, 'Form added to GUI shows language' );
	} );

	it( 'show Forms grammatical features', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addForm(
				id,
				{
					representations: {
						en: { language: 'en', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );
		LexemePage.addFormLink.click();

		assert( LexemePage.hasGramaticalFeatureList );
	} );

	it( 'has link to Form', () => {
		let formId,
			anchorId;

		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addForm(
				id,
				{
					representations: {
						en: { language: 'en', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );

		formId = ( LexemePage.formId.getText() ).split( '-' )[ 1 ];
		anchorId = LexemePage.getFormAnchor( 0 );

		assert.strictEqual( formId, anchorId );
	} );

} );

'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

describe( 'Form:Header', () => {
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
			const lexemeId = lexeme.id;
			return LexemeApi.addForm(
				lexemeId,
				{
					representations: {
						'en-ca': { language: 'en-ca', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => lexemeId );
		} ) );

		LexemePage.open( id );

		assert( LexemePage.formId.isExisting() );
	} );

	it( 'has representation', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const lexemeId = lexeme.id;
			return LexemeApi.addForm(
				lexemeId,
				{
					representations: {
						'en-ca': { language: 'en-ca', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => lexemeId );
		} ) );

		LexemePage.open( id );

		assert( LexemePage.hasRepresentation );
	} );

	it( 'has each representation having a language', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const lexemeId = lexeme.id;
			return LexemeApi.addForm(
				lexemeId,
				{
					representations: {
						en: { language: 'en', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => lexemeId );
		} ) );

		LexemePage.open( id );

		const form = LexemePage.getNthFormData( 0 );

		assert.equal( 'en', form.language, 'Form added to GUI shows language' );
	} );

	it( 'show Forms grammatical features', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const lexemeId = lexeme.id;
			return LexemeApi.addForm(
				lexemeId,
				{
					representations: {
						en: { language: 'en', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => lexemeId );
		} ) );

		LexemePage.open( id );
		LexemePage.addFormLink.click();

		assert( LexemePage.hasGramaticalFeatureList );
	} );

	it( 'has link to Form', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const lexemeId = lexeme.id;
			return LexemeApi.addForm(
				lexemeId,
				{
					representations: {
						en: { language: 'en', value: 'color' }
					},
					grammaticalFeatures: []
				}
			).then( () => lexemeId );
		} ) );

		LexemePage.open( id );

		const formId = ( LexemePage.formId.getText() ).split( '-' )[ 1 ];
		const anchorId = LexemePage.getFormAnchor( 0 );

		assert.strictEqual( formId, anchorId );
	} );

} );

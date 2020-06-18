'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	SensePage = require( '../pageobjects/sense.page' );

describe( 'Lexeme:Senses', () => {

	before( 'check logged in', () => {
		browser.deleteAllCookies();
		LoginPage.loginAdmin();
	} );

	it( 'can edit sense and save successfully', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addSense(
				id,
				{
					glosses: {
						en: { language: 'en', value: 'cats' }
					}
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );
		SensePage.editSensValueAndSubmit( 0, 'goats' );

		assert.strictEqual( 'goats', SensePage.getNthSenseData( 0 ).value );
	} );

	it( 'can not save senses with redundant languages', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addSense(
				id,
				{
					glosses: {
						en: { language: 'en', value: 'one' }
					}
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );
		SensePage.addGlossToNthSense( 0, 'two', 'en', false );

		assert.equal( SensePage.isNthSenseSubmittable( 0 ), false );
	} );

	it( 'shows the language and value in edit mode', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addSense(
				id,
				{
					glosses: {
						en: { language: 'en', value: 'goat' }
					}
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );
		SensePage.startEditingNthSense( 0 );
		const senseValues = SensePage.getNthSenseFormValues( 0 );
		assert.strictEqual( 'English (en)', senseValues.glosses[ 0 ].language );
		assert.strictEqual( 'goat', senseValues.glosses[ 0 ].value );
	} );

	it( 'removes sense when clicked on remove', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addSense(
				id,
				{
					glosses: {
						en: { language: 'en', value: 'goat' }
					}
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );
		SensePage.startEditingNthSense( 0 );
		SensePage.removeSense( 0 );
		SensePage.waitUntilStateChangeIsDone();

		assert.equal( false, SensePage.doesSenseExist() );
	} );

	it( 'Gloss value unchanged after editing was cancelled', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addSense(
				id,
				{
					glosses: {
						en: { language: 'en', value: 'goat' }
					}
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );
		SensePage.editSenseNoSubmit( 0, 'goats' );
		SensePage.cancelSenseEditing( 0 );
		const data = SensePage.getNthSenseData( 0 );

		assert.equal( 'goat', data.value );
	} );

	it( 'Removes Gloss', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addSense(
				id,
				{
					glosses: {
						en: { language: 'en', value: 'goat' }
					}
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );
		SensePage.addGlossToNthSense( 0, 'test', 'de', true );
		SensePage.startEditingNthSense( 0 );
		const glossCountBefore = SensePage.getNthSenseFormValues( 0 ).glosses.length;
		SensePage.removeGloss( 0, true );
		const glossCountAfter = SensePage.getNthSenseFormValues( 0 ).glosses.length;
		assert.notEqual( glossCountBefore, glossCountAfter );
	} );

} );

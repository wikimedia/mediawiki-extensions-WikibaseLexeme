'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	SensePage = require( '../pageobjects/sense.page' );

describe( 'Lexeme:Senses', () => {

	before( 'check logged in', () => {
		LoginPage.open();
		if ( !LexemePage.isUserLoggedIn() ) {
			LoginPage.loginAdmin();
		}
	} );

	it( 'can edit sense and save successfully', () => {
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
								en: { language: 'en', value: 'cats' }
							}
						}
					);
				} );
		} );
		LexemePage.open( id );
		SensePage.editSensValueAndSubmit( 0, 'goats' );

		assert.strictEqual( 'goats', SensePage.getNthSenseData( 0 ).value );
	} );

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

		SensePage.addGlossToNthSense( 0, 'two', 'en', false );

		assert.equal( SensePage.isNthSenseSubmittable( 0 ), false );
	} );

	it( 'shows the language and value in edit mode', () => {
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
		SensePage.startEditingNthSense( 0 );
		var senseValues = SensePage.getNthSenseFormValues( 0 );
		assert.strictEqual( 'English (en)', senseValues.glosses[ 0 ].language );
		assert.strictEqual( 'goat', senseValues.glosses[ 0 ].value );
	} );

	it( 'removes sense when clicked on remove', () => {
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
		SensePage.startEditingNthSense( 0 );
		SensePage.removeSense( 0 );
		SensePage.waitUntilStateChangeIsDone();

		assert.equal( false, SensePage.doesSenseExist() );
	} );

	it( 'Gloss value unchanged after editing was cancelled', () => {
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
		SensePage.editSenseNoSubmit( 0, 'goats' );
		SensePage.cancelSenseEditing( 0 );
		let data = SensePage.getNthSenseData( 0 );

		assert.equal( 'goat', data.value );
	} );

	it( 'Removes Gloss', () => {
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
		SensePage.addGlossToNthSense( 0, 'test', 'de', true );
		SensePage.startEditingNthSense( 0 );
		let glossCountBefore = SensePage.getNthSenseFormValues( 0 ).glosses.length;
		SensePage.removeGloss( 0, true );
		let glossCountAfter = SensePage.getNthSenseFormValues( 0 ).glosses.length;
		assert.notEqual( glossCountBefore, glossCountAfter );
	} );

} );

'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

describe( 'Lexeme:Lemma', () => {

	beforeEach( 'check logged in', () => {
		// Hook content is moved temporarily to individual tests for beta testing purposes
		// browser.deleteAllCookies();
		// LoginPage.loginAdmin();
	} );

	it( 'can be edited', () => {
		browser.deleteAllCookies();
		LoginPage.loginAdmin();
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		LexemePage.setFirstLemma( 'test lemma', 'en' );

		browser.call( () => LexemeApi.get( id )
			.then( ( lexeme ) => {
				assert.equal( 1, Object.keys( lexeme.lemmas ).length, 'No lemma added' );
				// eslint-disable-next-line dot-notation
				assert.equal( 'test lemma', lexeme.lemmas[ 'en' ].value, 'Lemma changed' );
			} ).catch( assert.fail )
		);

	} );

	it( 'can be edited multiple times', () => {
		browser.deleteAllCookies();
		LoginPage.loginAdmin();
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		LexemePage.setFirstLemma( 'test lemma', 'en' );

		LexemePage.setFirstLemma( 'another lemma', 'en-gb' );

		browser.call( () => LexemeApi.get( id ).then( ( lexeme ) => {
			assert.equal( 1, Object.keys( lexeme.lemmas ).length, 'No lemma added' );
			assert.equal( 'another lemma', lexeme.lemmas[ 'en-gb' ].value, 'Lemma changed' );
		} ) );

	} );

	it( 'can not save lemmas with redundant languages', () => {
		browser.deleteAllCookies();
		LoginPage.loginAdmin();
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );
		LexemePage.startHeaderEditMode();

		LexemePage.fillNthLemma( 0, 'some lemma', 'en' );
		LexemePage.fillNthLemma( 1, 'another lemma', 'en' );

		assert.equal( LexemePage.isHeaderSubmittable(), false );
	} );
} );

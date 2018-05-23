'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

describe( 'Lexeme:Lemma', () => {

	it( 'can be edited', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.setFirstLemma( 'test lemma', 'en' );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( 1, Object.keys( lexeme.lemmas ).length, 'No lemma added' );
					assert.equal( 'test lemma', lexeme.lemmas[ 'en' ].value, 'Lemma changed' );
				} );
		} );

	} );

	it( 'can be edited multiple times', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.setFirstLemma( 'test lemma', 'en' );

		LexemePage.setFirstLemma( 'another lemma', 'en-gb' );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( 1, Object.keys( lexeme.lemmas ).length, 'No lemma added' );
					assert.equal( 'another lemma', lexeme.lemmas[ 'en-gb' ].value, 'Lemma changed' );
				} );
		} );

	} );

} );

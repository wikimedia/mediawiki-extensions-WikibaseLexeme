'use strict';

const assert = require( 'assert' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'Lexeme:Statements', () => {
	it( 'can be added', () => {
		const lexemeId = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'string' ) );

		const testStringValue = Util.getTestString( 'value-' );
		LexemePage.open( lexemeId );
		LexemePage.addMainStatement( propertyId, testStringValue );

		let statementFromGui;
		browser.waitUntil( () => {
			statementFromGui = LexemePage.getNthStatementDataFromMainStatementGroup( 0, propertyId );
			return statementFromGui.value !== '';
		} );

		assert.equal( testStringValue, statementFromGui.value, 'Statement value added to GUI shows value' );

		browser.call( () => LexemeApi.get( lexemeId ).then( ( lexeme ) => {
			assert.equal( 1, lexeme.claims[ propertyId ].length, 'Statement to be found via API' );
			assert.equal( testStringValue, lexeme.claims[ propertyId ][ 0 ].mainsnak.datavalue.value, 'Correct value in API' );
		} ) );
	} );

} );

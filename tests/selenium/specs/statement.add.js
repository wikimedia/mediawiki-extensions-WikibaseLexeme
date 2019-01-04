'use strict';

const assert = require( 'assert' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' );

let WikibaseApi;
try {
	WikibaseApi = require( '../../../../Wikibase/repo/tests/selenium/wikibase.api' );
} catch ( e ) {
	try {
		WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
	} catch ( e2 ) {
		WikibaseApi = require( '../../../../Wikibase/repo/tests/selenium/wdio-wikibase/wikibase.api' );
	}
}

describe( 'Lexeme:Statements', () => {

	it( 'can be added', () => {
		let lexemeId,
			propertyId;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					lexemeId = lexeme.id;
				} );
		} );

		browser.call( () => {
			return WikibaseApi.getProperty( 'string' )
				.then( ( property ) => {
					propertyId = property;
				} );
		} );

		let testStringValue = Util.getTestString( 'value-' );
		LexemePage.open( lexemeId );
		LexemePage.addMainStatement( propertyId, testStringValue );

		let statementFromGui = LexemePage.getNthStatementDataFromMainStatementGroup( 0, propertyId );

		assert.equal( testStringValue, statementFromGui.value, 'Statement value added to GUI shows value' );

		browser.call( () => {
			return LexemeApi.get( lexemeId )
				.then( ( lexeme ) => {
					assert.equal( 1, lexeme.claims[ propertyId ].length, 'Statement to be found via API' );
					assert.equal( testStringValue, lexeme.claims[ propertyId ][ 0 ].mainsnak.datavalue.value, 'Correct value in API' );
				} );
		} );
	} );

} );

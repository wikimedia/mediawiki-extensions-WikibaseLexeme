'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	HistoryPage = require( '../pageobjects/history.page' ),
	UndoPage = require( '../pageobjects/undo.page' ),
	WikibaseApi = require( '../../../../Wikibase/repo/tests/selenium/wikibase.api' );

describe( 'Lexeme:Undoing', () => {

	it( 'removes grammatical features', () => {
		let lexemeId, featureOneId, featureTwoId;

		browser.call( () => {
			return Promise.all( [
				WikibaseApi.createItem( 'feature one' )
					.then( ( itemId ) => {
						featureOneId = itemId;
					} ),
				WikibaseApi.createItem( 'feature two' )
					.then( ( itemId ) => {
						featureTwoId = itemId;
					} ),
				LexemeApi.create()
					.then( ( lexeme ) => {
						lexemeId = lexeme.id;
					} )
			] )
			.then( () => {
				return LexemeApi.addForm(
					lexemeId,
					{
						representations: {
							en: { language: 'en', value: 'garlic' }
						},
						grammaticalFeatures: [ featureOneId ]
					}
				);
			} )
			.then( () => {
				return LexemeApi.editForm(
					lexemeId + '-F1',
					{
						representations: {
							en: { language: 'en', value: 'garlic' }
						},
						grammaticalFeatures: [ featureOneId, featureTwoId ]
					}
				);
			} );
		} );

		HistoryPage.open( lexemeId );

		// dismiss visual editor modal
		// has to happen after initial open, so that local storage works
		browser.localStorage( 'POST', { key: 've-beta-welcome-dialog', value: '1' } );
		HistoryPage.undoFirstRevision();

		UndoPage.save();

		assert.equal( 'feature one', LexemePage.getNthFormData( 0 ).grammaticalFeatures, 'Correct grammatical feature on the page' );

		browser.call( () => {
			return LexemeApi.get( lexemeId )
				.then( ( lexeme ) => {
					assert.equal( 1, lexeme.forms[ 0 ].grammaticalFeatures.length, 'Correct number of grammatical features in the API' );
					assert.equal( featureOneId, lexeme.forms[ 0 ].grammaticalFeatures[ 0 ], 'Correct grammatical feature in the API' );
				} );
		} );
	} );

} );

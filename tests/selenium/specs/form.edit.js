'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

let WikibaseApi;
try {
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
} catch ( e ) {
	WikibaseApi = require( '../../../../Wikibase/repo/tests/selenium/wdio-wikibase/wikibase.api' );
}

describe( 'Lexeme:Forms', () => {

	before( 'check logged in', () => {
		LoginPage.open();
		if ( !LexemePage.isUserLoggedIn() ) {
			LoginPage.loginAdmin();
		}
	} );

	it( 'can add representation', () => {
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

		LexemePage.addRepresentationToNthForm( 0, 'colour', 'en-gb' );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( 'color', lexeme.forms[ 0 ].representations[ 'en-ca' ].value, 'Old representation not changed' );
					assert.equal( 'colour', lexeme.forms[ 0 ].representations[ 'en-gb' ].value, 'New representation added' );
				} );
		} );

	} );

	it( 'can prefill representation language for lexeme with one lemma', () => {
		let id,
			formValues;

		browser.call( () => {
			return LexemeApi.create() // adds lexeme with on 'en' lemma
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.addFormLink.click();
		formValues = LexemePage.getNthFormFormValues( 0 );

		assert.equal( 'en', formValues.representations[ 0 ].language, 'Representation has default language from lemma' );
	} );

	it( 'can edit representation', () => {
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

		LexemePage.editRepresentationOfNthForm( 0, 'colour', 'en' );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( 'colour', lexeme.forms[ 0 ].representations.en.value, 'Representation changed' );
				} );
		} );

	} );

	it( 'can remove representation', () => {
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
								'en-ca': { language: 'en-ca', value: 'color' },
								'en-gb': { language: 'en-gb', value: 'colour' }
							},
							grammaticalFeatures: []
						}
					);
				} );
		} );

		LexemePage.open( id );

		LexemePage.removeLastRepresentationOfNthForm( 0 );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.ok( 'en-gb' in lexeme.forms[ 0 ].representations === false, 'Representation removed' );
				} );
		} );

	} );

	it( 'can not save representations with redundant languages', () => {
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

		LexemePage.addRepresentationToNthForm( 0, 'colour', 'en', false );

		assert.equal( LexemePage.isNthFormSubmittable( 0 ), false );
	} );

	it( 'can add grammatical feature', () => {
		let id,
			grammaticalFeatureId;

		browser.call( () => {
			return WikibaseApi.createItem()
				.then( ( itemId ) => {
					grammaticalFeatureId = itemId;
				} );
		} );

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

		LexemePage.addGrammaticalFeatureToNthForm( 0, grammaticalFeatureId );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.deepEqual( [ grammaticalFeatureId ], lexeme.forms[ 0 ].grammaticalFeatures );
				} );
		} );

	} );

	it( 'can remove first grammatical feature', () => {
		let id,
			grammaticalFeatureId;

		browser.call( () => {
			return WikibaseApi.createItem()
				.then( ( itemId ) => {
					grammaticalFeatureId = itemId;
				} );
		} );
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
								de: { language: 'de', value: 'lorem' }
							},
							grammaticalFeatures: []
						}
					);
				} );
		} );

		LexemePage.open( id );

		LexemePage.addGrammaticalFeatureToNthForm( 0, grammaticalFeatureId );

		LexemePage.removeGrammaticalFeatureFromNthForm( 0 );

		browser.call( () => {
			return LexemeApi.get( id )
				.then( ( lexeme ) => {
					assert.equal( grammaticalFeatureId === lexeme.forms[ 0 ].grammaticalFeatures[ 0 ], false, 'grammatical feature removed'
					);
				} );
		} );

	} );

	it( 'can cancel form addition', () => {
		let id;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.addFormLink.click();

		LexemePage.addFormCancelLink.click();

		assert.equal( LexemePage.formId.isExisting(), false, 'No form added' );

	} );

	it( 'has statement list', () => {
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

		assert.equal( LexemePage.formStatementList.isExisting(), true );
	} );

	it( 'FormId counter is not decremented when addForm is undone', () => {
		let id,
			oldFormID,
			newFormID,
			isNotDecremented;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.addForm( 'Foo', 'en' );
		oldFormID = ( LexemePage.formId.getText() ).split( '-F' )[ 1 ];

		LexemePage.undoLatestRevision();

		LexemePage.addForm( 'Yacht', 'de' );
		newFormID = ( LexemePage.formId.getText() ).split( '-F' )[ 1 ];

		isNotDecremented = ( newFormID > oldFormID );

		assert.ok( isNotDecremented, 'FormId counter is not decremented' );

	} );

	it( 'FormId counter is not decremented when old revision is restored', () => {
		let id,
			oldFormID,
			newFormID,
			isNotDecremented;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.addForm( 'Foo', 'en' );
		oldFormID = ( LexemePage.formId.getText() ).split( '-F' )[ 1 ];

		LexemePage.restorePreviousRevision();

		LexemePage.addForm( 'Yacht', 'de' );
		newFormID = ( LexemePage.formId.getText() ).split( '-F' )[ 1 ];

		isNotDecremented = ( newFormID > oldFormID );

		assert.ok( isNotDecremented, 'FormId counter is not decremented' );

	} );

	it( 'change multi-variant representations', () => {
		let id,
			formRepresentation;

		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.addForm( 'colors', 'en-ca' );

		LexemePage.addRepresentationToNthForm( 0, 'colours', 'en-gb' );

		browser.refresh();

		LexemePage.addFormLink.waitForVisible();// just to make sure the page loaded completely

		formRepresentation = LexemePage.getNthFormFormValuesAfterSave( 0 );

		assert.equal( 'colors', formRepresentation.representations[ 0 ].value );
		assert.equal( 'en-ca', formRepresentation.representations[ 0 ].language );
		assert.equal( 'colours', formRepresentation.representations[ 1 ].value );
		assert.equal( 'en-gb', formRepresentation.representations[ 1 ].language );

	} );

	it( 'can edit statements on a new Form', () => {
		let id,
			statementObj,
			statementProperty,
			statementPropertyId,
			statementValue = 'Some string';

		browser.call( () => {
			return WikibaseApi.getProperty( 'string' )
				.then( ( propertyId ) => {
					statementPropertyId = propertyId;
				} )
				.then( () => {
					return WikibaseApi.getEntity( statementPropertyId )
						.then( ( entityObj ) => {
							statementProperty = entityObj;
						} );
				} );
		} );
		browser.call( () => {
			return LexemeApi.create()
				.then( ( lexeme ) => {
					id = lexeme.id;
				} );
		} );

		LexemePage.open( id );

		LexemePage.addForm( 'newForm', 'en' );

		LexemePage.addStatementToNthForm( 0, statementPropertyId, statementValue );

		statementObj = LexemePage.getNthFormStatement( 0 );

		assert.equal( statementPropertyId, statementObj.propertyId[ 1 ] );
		assert.equal( statementValue, statementObj.value );

	} );

} );

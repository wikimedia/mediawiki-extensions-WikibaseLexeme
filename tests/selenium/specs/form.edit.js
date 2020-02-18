'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../lexeme.api' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	loginAdmin = require( '../loginAdmin' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'Lexeme:Forms', () => {

	before( 'check logged in', () => {
		browser.deleteAllCookies();
		loginAdmin();
	} );

	it( 'can add representation', () => {
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

		LexemePage.addRepresentationToNthForm( 0, 'colour', 'en-gb' );

		browser.call( () => LexemeApi.get( id ).then( ( lexeme ) => {
			assert.equal( 'color', lexeme.forms[ 0 ].representations[ 'en-ca' ].value, 'Old representation not changed' );
			assert.equal( 'colour', lexeme.forms[ 0 ].representations[ 'en-gb' ].value, 'New representation added' );
		} ) );

	} );

	it( 'can prefill representation language for lexeme with one lemma', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		LexemePage.addFormLink.click();
		const formValues = LexemePage.getNthFormFormValues( 0 );

		assert.equal( 'en', formValues.representations[ 0 ].language, 'Representation has default language from lemma' );
	} );

	it( 'can edit representation', () => {
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

		LexemePage.editRepresentationOfNthForm( 0, 'colour', 'en' );

		browser.call( () => LexemeApi.get( id ).then( ( lexeme ) => {
			assert.equal( 'colour', lexeme.forms[ 0 ].representations.en.value, 'Representation changed' );
		} ) );

	} );

	it( 'can remove representation', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addForm(
				id,
				{
					representations: {
						'en-ca': { language: 'en-ca', value: 'color' },
						'en-gb': { language: 'en-gb', value: 'colour' }
					},
					grammaticalFeatures: []
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );

		LexemePage.removeLastRepresentationOfNthForm( 0 );

		browser.call( () => LexemeApi.get( id ).then( ( lexeme ) => {
			assert.ok( 'en-gb' in lexeme.forms[ 0 ].representations === false, 'Representation removed' );
		} ) );

	} );

	it( 'can not save representations with redundant languages', () => {
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

		LexemePage.addRepresentationToNthForm( 0, 'colour', 'en', false );

		assert.equal( LexemePage.isNthFormSubmittable( 0 ), false );
	} );

	it( 'can add grammatical feature', () => {
		const grammaticalFeatureId = browser.call( () => WikibaseApi.createItem() );
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

		LexemePage.addGrammaticalFeatureToNthForm( 0, grammaticalFeatureId );

		browser.call( () => LexemeApi.get( id ).then( ( lexeme ) => {
			assert.deepEqual( [ grammaticalFeatureId ], lexeme.forms[ 0 ].grammaticalFeatures );
		} ) );

	} );

	it( 'can remove first grammatical feature', () => {
		const grammaticalFeatureId = browser.call( () => WikibaseApi.createItem() );
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => {
			const id = lexeme.id;
			return LexemeApi.addForm(
				id,
				{
					representations: {
						de: { language: 'de', value: 'lorem' }
					},
					grammaticalFeatures: []
				}
			).then( () => id );
		} ) );

		LexemePage.open( id );

		LexemePage.addGrammaticalFeatureToNthForm( 0, grammaticalFeatureId );

		LexemePage.removeGrammaticalFeatureFromNthForm( 0 );

		browser.call( () => LexemeApi.get( id ).then( ( lexeme ) => {
			assert.equal(
				grammaticalFeatureId === lexeme.forms[ 0 ].grammaticalFeatures[ 0 ],
				false,
				'grammatical feature removed'
			);
		} ) );

	} );

	// T231685
	it.skip( 'can cancel form addition', () => {
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		LexemePage.addFormLink.click();

		LexemePage.addFormCancelLink.click();

		assert.equal( LexemePage.formId.isExisting(), false, 'No form added' );

	} );

	it( 'has statement list', () => {
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

		assert.equal( LexemePage.formStatementList.isExisting(), true );
	} );

	it( 'FormId counter is not decremented when addForm is undone', () => {
		let oldFormID,
			newFormID,
			isNotDecremented;

		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

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
		let oldFormID,
			newFormID,
			isNotDecremented;

		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

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
		let formRepresentation;

		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		LexemePage.addForm( 'colors', 'en-ca' );

		LexemePage.addRepresentationToNthForm( 0, 'colours', 'en-gb' );

		browser.refresh();

		LexemePage.addFormLink.waitForDisplayed();// just to make sure the page loaded completely

		formRepresentation = LexemePage.getNthFormFormValuesAfterSave( 0 );

		assert.equal( 'colors', formRepresentation.representations[ 0 ].value );
		assert.equal( 'en-ca', formRepresentation.representations[ 0 ].language );
		assert.equal( 'colours', formRepresentation.representations[ 1 ].value );
		assert.equal( 'en-gb', formRepresentation.representations[ 1 ].language );

	} );

	it( 'can edit statements on a new Form', () => {
		const statementPropertyId = browser.call( () => WikibaseApi.getProperty( 'string' )
			.then( ( statementPropertyId ) => {
				return WikibaseApi.getEntity( statementPropertyId )
					.then( () => statementPropertyId );
			} )
		);
		const id = browser.call( () => LexemeApi.create().then( ( lexeme ) => lexeme.id ) );

		LexemePage.open( id );

		LexemePage.addForm( 'newForm', 'en' );

		const statementValue = 'Some string';

		LexemePage.addStatementToNthForm( 0, statementPropertyId, statementValue );

		const statementObj = LexemePage.getNthFormStatement( 0 );

		assert.equal( statementPropertyId, statementObj.propertyId[ 1 ] );
		assert.equal( statementValue, statementObj.value );

	} );

} );

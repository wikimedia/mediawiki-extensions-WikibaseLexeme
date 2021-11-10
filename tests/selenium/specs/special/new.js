'use strict';

// For use inside browser.execute
/* global window */

/* eslint-disable mocha/no-setup-in-describe */

const assert = require( 'assert' ),
	NewLexemePage = require( '../../pageobjects/newlexeme.page' ),
	LexemePage = require( '../../pageobjects/lexeme.page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../../lexeme.api' ),
	MWApi = require( 'wdio-mediawiki/Api' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'NewLexeme:Page', () => {

	it( 'request with "createpage" right shows form', () => {
		NewLexemePage.open();

		assert.ok( NewLexemePage.showsForm() );
	} );

	/**
	 * This block can only be executed if there is a property configured that allows
	 * the application to derive an ISO 639-1 language code from the item that is created
	 * here as 'language', see README.md.
	 */
	describe( 'with default LexemeLanguageCodePropertyId configured', () => {
		let lexemeLanguageCodePropertyId;

		before( function () {
			NewLexemePage.open();

			Util.waitForModuleState( 'wikibase.lexeme.config.LexemeLanguageCodePropertyIdConfig' );
			lexemeLanguageCodePropertyId = browser.executeAsync( ( done ) => {
				done( window.mw.config.get( 'LexemeLanguageCodePropertyId' ) );
			} );

			if ( lexemeLanguageCodePropertyId === null ) {
				this.skip( 'LexemeLanguageCodePropertyId not set' );
			}

			const lexemeLanguageCodeProperty = browser.call( () => WikibaseApi.getEntity( lexemeLanguageCodePropertyId ) );

			if ( lexemeLanguageCodeProperty.missing === '' ) {
				this.skip( 'Configured LexemeLanguageCodePropertyId not a known property' );
			}
		} );

		it( 'can create lexeme with language item bearing language code statement', () => {
			const lemma = Util.getTestString( 'lemma-' ),
				language = Util.getTestString( 'language-' ),
				languageItemsLanguageCode = 'en',
				lexicalCategory = Util.getTestString( 'lexicalCategory-' );

			NewLexemePage.open();

			const claims = [
				{
					mainsnak: {
						snaktype: 'value',
						property: lexemeLanguageCodePropertyId,
						datavalue: {
							value: languageItemsLanguageCode,
							type: 'string'
						}
					},
					type: 'statement',
					rank: 'normal'
				}
			];

			const languageId = browser.call( () => WikibaseApi.createItem( language, { claims } ) );

			const lexicalCategoryId = browser.call( () => WikibaseApi.createItem( lexicalCategory ) );

			NewLexemePage.createLexeme(
				lemma,
				languageId,
				lexicalCategoryId
			);

			LexemePage.lemmaContainer.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

			const lexemeId = LexemePage.headerId;

			browser.call( () => LexemeApi.get( lexemeId ).then( ( lexeme ) => {
				assert.equal( lexeme.lemmas[ languageItemsLanguageCode ].value, lemma );
				assert.equal( lexeme.language, languageId );
				assert.equal( lexeme.lexicalCategory, lexicalCategoryId );
			} ) );
		} );
	} );

	describe( 'for different lemma languages', () => {
		const assertions = [ 'en', 'mis' ];

		assertions.forEach( ( language ) => {
			it( `can create lexeme with language item not bearing language code statement and ${language} lemma language`, () => {
				const lemma = Util.getTestString( 'lemma-' ),
					wannabeLanguage = Util.getTestString( 'wannabeLanguage-' ),
					lemmaLanguageCode = language,
					lexicalCategory = Util.getTestString( 'lexicalCategory-' );

				NewLexemePage.open();

				const wannabeLanguageId = browser.call( () => WikibaseApi.createItem( wannabeLanguage ) );

				const lexicalCategoryId = browser.call( () => WikibaseApi.createItem( lexicalCategory ) );

				NewLexemePage.createLexeme(
					lemma,
					wannabeLanguageId,
					lexicalCategoryId,
					lemmaLanguageCode
				);

				LexemePage.lemmaContainer.waitForDisplayed( { timeout: browser.config.nonApiTimeout } );

				const lexemeId = LexemePage.headerId;

				browser.call( () => LexemeApi.get( lexemeId ).then( ( lexeme ) => {
					assert.equal(
						JSON.stringify( lexeme.lemmas ),
						JSON.stringify( {
							[ lemmaLanguageCode ]: {
								language: lemmaLanguageCode,
								value: lemma
							}
						} )
					);
					assert.equal( lexeme.language, wannabeLanguageId );
					assert.equal( lexeme.lexicalCategory, lexicalCategoryId );
				} ) );
			} );
		} );
	} );

	describe( 'with language item not bearing language code statement set on form submission and failure to validate', () => {
		it( 'is possible to immediately see lemmaLanguageCode field', () => {
			const lemma = Util.getTestString( 'lemma-' ),
				languageItem = Util.getTestString( 'wannabeLanguage-' ),
				lexicalCategory = Util.getTestString( 'lexicalCategory-' );

			NewLexemePage.open();

			const languageItemId = browser.call( () => WikibaseApi.createItem( languageItem ) );

			const lexicalCategoryId = browser.call( () => WikibaseApi.createItem( lexicalCategory ) );

			NewLexemePage.setLemma( lemma );
			NewLexemePage.setLexemeLanguage( languageItemId );
			NewLexemePage.setLexicalCategory( lexicalCategoryId );

			assert.ok( NewLexemePage.showsLemmaLanguageField() );

			NewLexemePage.clickSubmit();

			assert.ok( NewLexemePage.showsLemmaLanguageField() );
		} );
	} );

	describe( 'with form parameters included in query string and language item not bearing language code statement', () => {
		it( 'all forms are visible in UI with query parameters', () => {
			const lemma = Util.getTestString( 'lemma-' ),
				languageItem = Util.getTestString( 'wannabeLanguage-' ),
				lexicalCategory = Util.getTestString( 'lexicalCategory-' ),
				lemmaLanguage = 'fooLanguageCode';

			const languageItemId = browser.call( () => WikibaseApi.createItem( languageItem ) );

			const lexicalCategoryId = browser.call( () => WikibaseApi.createItem( lexicalCategory ) );

			NewLexemePage.open( {
				'lexeme-language': languageItemId,
				'lemma-language': lemmaLanguage,
				lexicalcategory: lexicalCategoryId,
				lemma: lemma
			} );

			assert.ok( NewLexemePage.showsLemmaLanguageField() );

			assert.equal( NewLexemePage.getLemma(), lemma );
			assert.equal( NewLexemePage.getLexemeLanguage(), languageItemId );
			assert.equal( NewLexemePage.getLexicalCategory(), lexicalCategoryId );
			assert.equal( NewLexemePage.getLemmaLanguage(), lemmaLanguage );
		} );
	} );

	describe( 'when blocked', () => {
		beforeEach( () => {
			return browser.call( async () => MWApi.blockUser( await MWApi.bot() ) );

		} );

		afterEach( () => {
			return browser.call( async () => MWApi.unblockUser( await MWApi.bot() ) );
		} );

		it( 'is not possible to edit', () => {
			NewLexemePage.open();

			assert.strictEqual( NewLexemePage.formCurrentlyVisible(), false );
			assert.ok( NewLexemePage.isUserBlockedErrorVisible() );
		} );
	} );

} );

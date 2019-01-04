'use strict';

const assert = require( 'assert' ),
	NewLexemePage = require( '../../pageobjects/newlexeme.page' ),
	LexemePage = require( '../../pageobjects/lexeme.page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../../lexeme.api' );

let WikibaseApi;
try {
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
} catch ( e ) {
	WikibaseApi = require( '../../../../../Wikibase/repo/tests/selenium/wdio-wikibase/wikibase.api' );
}

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
		let lexemeLanguageCodePropertyId,
			lexemeLanguageCodeProperty;

		before( function () {
			NewLexemePage.open();

			// TODO how to do this nicely?
			browser.waitUntil( () => {
				return browser.execute( () => {
					return ( typeof window.mw.loader === 'object' && typeof window.mw.loader.using === 'function' );
				} ).value === true;
			} );

			lexemeLanguageCodePropertyId = browser.executeAsync( ( done ) => {
				window.mw.loader.using( [ 'wikibase.lexeme.config.LexemeLanguageCodePropertyIdConfig' ], function () {
					done( window.mw.config.get( 'LexemeLanguageCodePropertyId' ) );
				} );
			} ).value;

			if ( lexemeLanguageCodePropertyId === null ) {
				this.skip( 'LexemeLanguageCodePropertyId not set' );
			}

			browser.call( () => {
				return WikibaseApi.getEntity( lexemeLanguageCodePropertyId )
					.then( ( entity ) => {
						lexemeLanguageCodeProperty = entity;
					} );
			} );

			if ( lexemeLanguageCodeProperty.missing === '' ) {
				this.skip( 'Configured LexemeLanguageCodePropertyId not a known property' );
			}
		} );

		it( 'can create lexeme with language item bearing language code statement', () => {
			let lemma = Util.getTestString( 'lemma-' ),
				language = Util.getTestString( 'language-' ),
				languageItemsLanguageCode = 'en',
				lexicalCategory = Util.getTestString( 'lexicalCategory-' ),
				languageId, lexicalCategoryId,
				lexemeId;

			NewLexemePage.open();

			let claims = [
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

			browser.call( () => {
				return WikibaseApi.createItem( language, { claims } )
					.then( ( id ) => {
						languageId = id;
					} );
			} );

			browser.call( () => {
				return WikibaseApi.createItem( lexicalCategory )
					.then( ( id ) => {
						lexicalCategoryId = id;
					} );
			} );

			NewLexemePage.createLexeme(
				lemma,
				languageId,
				lexicalCategoryId
			);

			LexemePage.lemmaContainer.waitForVisible();

			lexemeId = LexemePage.headerId;

			browser.call( () => {
				return LexemeApi.get( lexemeId )
					.then( ( lexeme ) => {
						assert.equal( lexeme.lemmas[ languageItemsLanguageCode ].value, lemma );
						assert.equal( lexeme.language, languageId );
						assert.equal( lexeme.lexicalCategory, lexicalCategoryId );
					} );
			} );
		} );
	} );

	describe( 'for different lemma languages', () => {
		const assertions = [ 'en', 'mis' ];

		assertions.forEach( ( language ) => {
			it( `can create lexeme with language item not bearing language code statement and ${language} lemma language`, () => {
				let lemma = Util.getTestString( 'lemma-' ),
					wannabeLanguage = Util.getTestString( 'wannabeLanguage-' ),
					lemmaLanguageCode = language,
					lexicalCategory = Util.getTestString( 'lexicalCategory-' ),
					wannabeLanguageId, lexicalCategoryId,
					lexemeId;

				NewLexemePage.open();

				browser.call( () => {
					return WikibaseApi.createItem( wannabeLanguage )
						.then( ( id ) => {
							wannabeLanguageId = id;
						} );
				} );

				browser.call( () => {
					return WikibaseApi.createItem( lexicalCategory )
						.then( ( id ) => {
							lexicalCategoryId = id;
						} );
				} );

				NewLexemePage.createLexeme(
					lemma,
					wannabeLanguageId,
					lexicalCategoryId,
					lemmaLanguageCode
				);

				LexemePage.lemmaContainer.waitForVisible();

				lexemeId = LexemePage.headerId;

				browser.call( () => {
					return LexemeApi.get( lexemeId )
						.then( ( lexeme ) => {
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
						} );
				} );
			} );
		} );
	} );

	describe( 'with language item not bearing language code statement set on form submission and failure to validate', () => {
		it( 'is possible to immediately see lemmaLanguageCode field', () => {
			let lemma = Util.getTestString( 'lemma-' ),
				languageItem = Util.getTestString( 'wannabeLanguage-' ),
				lexicalCategory = Util.getTestString( 'lexicalCategory-' ),
				languageItemId,
				lexicalCategoryId;

			NewLexemePage.open();

			browser.call( () => {
				return Promise.all( [
					WikibaseApi.createItem( languageItem ),
					WikibaseApi.createItem( lexicalCategory )
				] ).then( ( ids ) => {
					languageItemId = ids[ 0 ];
					lexicalCategoryId = ids[ 1 ];
				} );
			} );

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
			let lemma = Util.getTestString( 'lemma-' ),
				languageItem = Util.getTestString( 'wannabeLanguage-' ),
				lexicalCategory = Util.getTestString( 'lexicalCategory-' ),
				lemmaLanguage = 'fooLanguageCode',
				languageItemId,
				lexicalCategoryId;

			browser.call( () => {
				return Promise.all( [
					WikibaseApi.createItem( languageItem ),
					WikibaseApi.createItem( lexicalCategory )
				] ).then( ( ids ) => {
					languageItemId = ids[ 0 ];
					lexicalCategoryId = ids[ 1 ];
				} );
			} );

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

} );

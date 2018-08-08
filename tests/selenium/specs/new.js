'use strict';

const assert = require( 'assert' ),
	NewLexemePage = require( '../pageobjects/newlexeme.page' ),
	LexemePage = require( '../pageobjects/lexeme.page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../lexeme.api' ),
	WikibaseApi = require( '../../../../Wikibase/repo/tests/selenium/wikibase.api' );

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

			lexemeLanguageCodePropertyId = browser.execute( () => {
				return window.mw.config.get( 'LexemeLanguageCodePropertyId' );
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

} );

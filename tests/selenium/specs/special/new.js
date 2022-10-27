'use strict';

const assert = require( 'assert' ),
	NewLexemePage = require( '../../pageobjects/newlexeme.page' ),
	LexemePage = require( '../../pageobjects/lexeme.page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../../lexeme.api' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'NewLexeme:Page', () => {

	it( 'request with "createpage" right shows form', () => {
		NewLexemePage.open();

		assert.ok( NewLexemePage.showsForm() );
	} );

	it( 'can create lexeme', () => {
		const lemma = Util.getTestString( 'lemma-' ),
			language = Util.getTestString( 'language-' ),
			languageItemsLanguageCode = 'aa',
			lexicalCategory = Util.getTestString( 'lexicalCategory-' );

		NewLexemePage.open();

		const languageId = browser.call( () => WikibaseApi.createItem( language ) );

		const lexicalCategoryId = browser.call( () => WikibaseApi.createItem( lexicalCategory ) );

		browser.log( 'Special:NewLexeme page opened and Items for language and lexical category created.' );
		NewLexemePage.createLexeme(
			lemma,
			languageId,
			lexicalCategoryId,
			languageItemsLanguageCode
		);
		browser.log( 'Data entered into form and submit button clicked.' );
		const navigationTimeout = 10000;
		LexemePage.lemmaContainer.waitForDisplayed( { timeout: browser.config.waitforTimeout + navigationTimeout } );
		browser.log( 'Lexeme has been created via the API and the Page for the newly created Lexeme is being displayed.' );

		const lexemeId = LexemePage.headerId;

		browser.call( () => LexemeApi.get( lexemeId ).then( ( lexeme ) => {
			assert.equal( lexeme.lemmas[ languageItemsLanguageCode ].value, lemma );
			assert.equal( lexeme.language, languageId );
			assert.equal( lexeme.lexicalCategory, lexicalCategoryId );
		} ) );
		browser.log( 'Lexeme data asserted via the API.' );
	} );

} );

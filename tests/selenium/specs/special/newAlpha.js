'use strict';

const assert = require( 'assert' ),
	NewLexemePage = require( '../../pageobjects/newlexemeAlpha.page' ),
	LexemePage = require( '../../pageobjects/lexeme.page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../../lexeme.api' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'NewLexemeAlpha:Page', () => {

	it( 'request with "createpage" right shows form', () => {
		NewLexemePage.open();

		assert.ok( NewLexemePage.showsForm() );
	} );

	it( 'can create lexeme', () => {
		const lemma = Util.getTestString( 'lemma-' ),
			language = Util.getTestString( 'language-' ),
			languageItemsLanguageCode = 'en',
			lexicalCategory = Util.getTestString( 'lexicalCategory-' );

		NewLexemePage.open();

		const languageId = browser.call( () => WikibaseApi.createItem( language ) );

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

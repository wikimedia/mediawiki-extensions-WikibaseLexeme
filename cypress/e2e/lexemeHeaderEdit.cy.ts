import { LexemePage } from '../support/pageObjects/LexemePage';

import { Util } from 'cypress-wikibase-api';

const lexemePage = new LexemePage();

describe( 'Lexeme:Header', () => {

	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).as( 'lexemeId' );
		const testLabel = Util.getTestString( 'editHeaderTestItem-' );
		cy.task( 'MwApi:CreateItem', { label: testLabel } ).as( 'itemId' );
	} );

	it( 'can edit the language of a Lexeme', () => {
		cy.getStringAlias( '@lexemeId' )
			.then( ( lexemeId ) => cy.getStringAlias( '@itemId' )
				.then( ( itemId ) => {
					lexemePage.open( lexemeId );
					lexemePage.startHeaderEditMode();

					lexemePage.setLexemeLanguageItem( itemId );
					lexemePage.headerSaveButtonNotPresent();
					cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
						.then( ( lexemeData ) => {
							expect( lexemeData.language ).to.eq( itemId );
						} );
				} ) );
	} );

	it( 'can edit the lexical category of a Lexeme', () => {
		cy.getStringAlias( '@lexemeId' )
			.then( ( lexemeId ) => cy.getStringAlias( '@itemId' )
				.then( ( itemId ) => {
					lexemePage.open( lexemeId );
					lexemePage.startHeaderEditMode();

					lexemePage.setLexicalCategoryItem( itemId );
					lexemePage.headerSaveButtonNotPresent();
					cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
						.then( ( lexemeData ) => {
							expect( lexemeData.lexicalCategory ).to.eq( itemId );
						} );
				} ) );
	} );

} );

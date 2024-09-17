import { LexemePage } from '../support/pageObjects/LexemePage';

import { Util } from 'cypress-wikibase-api';

const lexemePage = new LexemePage();

describe( 'Lexeme:Header', () => {

	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).as( 'lexemeId' );
		const testLabel = Util.getTestString( 'editHeaderTestItem-' );
		cy.task( 'MwApi:CreateItem', { label: testLabel } ).as( 'itemId' );
	} );

	it( 'can edit the language and lexical category of a Lexeme', () => {
		cy.getStringAlias( '@lexemeId' )
			.then( ( lexemeId ) => cy.getStringAlias( '@itemId' )
				.then( ( itemId ) => {
					lexemePage.open( lexemeId );

					// edit the language
					lexemePage.startHeaderEditMode();
					lexemePage.setLexemeLanguageItem( itemId );

					// edit the lexical category
					lexemePage.startHeaderEditMode();
					lexemePage.setLexicalCategoryItem( itemId );

					// verify both via the API
					cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
						.then( ( lexemeData ) => {
							expect( lexemeData.language ).to.eq( itemId );
							expect( lexemeData.lexicalCategory ).to.eq( itemId );
						} );
				} ) );
	} );

} );

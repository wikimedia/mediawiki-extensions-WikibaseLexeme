import { LexemePage } from '../support/pageObjects/LexemePage';

const lexemePage = new LexemePage();

describe( 'Lexeme:Lemma', () => {
	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).as( 'lexemeId' );
	} );

	it( 'can be edited', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId ) => {
			lexemePage.open( lexemeId );
			lexemePage.setNthLemma( 0, 'test lemma', 'en' );

			cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
				.should( ( lexemeObject ) => {
					expect( Object.keys( lexemeObject.lemmas ).length ).to.eq( 1 );
					expect( lexemeObject.lemmas.en.value ).to.eq( 'test lemma' );
				} );
		} );
	} );

	it( 'can be edited multiple times', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId ) => {
			lexemePage.open( lexemeId );

			lexemePage.setNthLemma( 0, 'test lemma', 'en' );
			lexemePage.setNthLemma( 0, 'another lemma', 'en-gb' );

			cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
				.should( ( lexemeObject ) => {
					expect( Object.keys( lexemeObject.lemmas ).length ).to.eq( 1 );
					expect( lexemeObject.lemmas[ 'en-gb' ].value ).to.eq( 'another lemma' );
				} );
		} );
	} );

	it( 'can not save lemmas with redundant languages', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId ) => {
			lexemePage.open( lexemeId );

			lexemePage.startHeaderEditMode();
			lexemePage.fillNthLemma( 0, 'some lemma', 'en' );
			lexemePage.fillNthLemma( 1, 'another lemma', 'en' );

			lexemePage.getHeaderSaveButton().should( 'be.disabled' );
			lexemePage.getRedundantLanguageWarning();
		} );
	} );
} );

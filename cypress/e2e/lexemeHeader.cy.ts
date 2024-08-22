import { LexemePage } from '../support/pageObjects/LexemePage';

const lexemePage = new LexemePage();

describe( 'Lexeme:Header', () => {

	it( 'shows id', () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).then( ( lexemeId: string ) => {
			lexemePage.open( lexemeId );

			lexemePage.getHeaderId().should( 'eq', lexemeId );
		} );
	} );

} );

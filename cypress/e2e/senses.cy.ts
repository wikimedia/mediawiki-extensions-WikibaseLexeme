import { LexemePage } from '../support/pageObjects/LexemePage';
import { SensesPage } from '../support/pageObjects/SensesPage';

const lexemePage = new LexemePage();
const sensesPage = new SensesPage();

describe( 'Lexeme:Senses', () => {
	before( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).as( 'lexemeId' );
	} );

	it( 'Sense header and container exist', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			lexemePage.open( lexemeId );

			sensesPage.getSensesHeaderElement();
			sensesPage.getSenseIdElement().should( 'not.exist' );
			sensesPage.addSense( 'en', 'Yacht' );
			sensesPage.getSensesHeaderElement().should( 'have.text', 'Senses' );

			sensesPage.getSenseIdElement().should( 'have.text', lexemeId + '-S1' );
		} );
	} );
} );

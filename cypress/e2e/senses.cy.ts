import { LexemePage } from '../support/pageObjects/LexemePage';
import { SensesSection } from '../support/pageObjects/SensesSection';

const lexemePage = new LexemePage();
const sensesSection = new SensesSection();

describe( 'Lexeme:Senses', () => {
	before( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).as( 'lexemeId' );
	} );

	it( 'Sense header and container exist', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			lexemePage.open( lexemeId );

			sensesSection.getSensesHeaderElement();
			sensesSection.getSenseIdElement().should( 'not.exist' );
			sensesSection.addSense( 'en', 'Yacht' );
			sensesSection.getSensesHeaderElement().should( 'have.text', 'Senses' );

			sensesSection.getSenseIdElement().should( 'have.text', lexemeId + '-S1' );
		} );
	} );
} );

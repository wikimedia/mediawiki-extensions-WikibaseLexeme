import { LexemePage } from '../support/pageObjects/LexemePage';
import { FormsSection } from '../support/pageObjects/FormsSection';

const lexemePage = new LexemePage();
const formsSection = new FormsSection();

describe( 'Lexeme:Forms', () => {

	it( 'can be added', () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).then( ( lexemeId: string ) => {
			lexemePage.open( lexemeId );

			formsSection.addForm( 'de', 'Yacht' );

			formsSection.getNthFormData( 0 ).its( 'value' ).should( 'eq', 'Yacht' );
			formsSection.getNthFormData( 0 ).its( 'language' ).should( 'eq', 'de' );

			cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
				.should( ( lexemeObject ) => {
					expect( Object.keys( lexemeObject.forms ).length ).to.eq( 1 );
					expect( lexemeObject.forms[ 0 ].representations.de.value ).to.eq( 'Yacht' );
				} );
		} );
	} );

} );

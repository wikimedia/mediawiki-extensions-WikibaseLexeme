import { LexemePage } from '../support/pageObjects/LexemePage';
import { FormsSection } from '../support/pageObjects/FormsSection';

const lexemePage = new LexemePage();
const formsSection = new FormsSection();

describe( 'Lexeme:Forms', () => {

	it( 'can be open the add Form, cancel the add, open it again and save. ' +
		'it prefills the language in the add-form', () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).then( ( lexemeId: string ) => {
			lexemePage.open( lexemeId );

			// Check that we can open the form, and close it with 'Cancel'
			formsSection.openAddFormForm();
			// Check that the form language is pre-filled
			formsSection.getLastRepresentationEditFromInputLanguage()
				.invoke( 'val' ).should( 'eq', 'en' );

			formsSection.cancelAddForm();
			formsSection.getFormListItem().should( 'not.exist' );

			// Check that we can add a Form
			formsSection.addForm( 'de', 'Yacht' );

			formsSection.getNthFormLastRepresentationData( 0 )
				.its( 'value' ).should( 'eq', 'Yacht' );
			formsSection.getNthFormLastRepresentationData( 0 )
				.its( 'language' ).should( 'eq', 'de' );

			cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
				.should( ( lexemeObject ) => {
					expect( Object.keys( lexemeObject.forms ).length ).to.eq( 1 );
					expect( lexemeObject.forms[ 0 ].representations.de.value ).to.eq( 'Yacht' );
				} );
		} );
	} );

} );

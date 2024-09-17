import { LexemePage } from '../support/pageObjects/LexemePage';
import { SensesSection } from '../support/pageObjects/SensesSection';

const lexemePage = new LexemePage();
const sensesSection = new SensesSection();

describe( 'Lexeme:Senses', () => {

	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).as( 'lexemeId' )
			.then( ( lexemeId ) => {
				cy.task( 'MwLexemeApi:AddSense', { lexemeId, senseData: {
					glosses: {
						en: { language: 'en', value: 'cats' }
					}
				} } );
			} );
	} );

	it(
		'Shows the language and value in edit mode. It can save edits successfully, ' +
			'while trimming whitespace from the new value.',
		() => {
			cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
				lexemePage.open( lexemeId );
				sensesSection.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'cats' );

				// Check that the original values are show in edit mode
				sensesSection.startEditingNthSense( 0 );
				sensesSection.getNthSenseFormValues( 0 ).then( ( senseValues ) => {
					expect( senseValues.glosses[ 0 ].language ).to.eq( 'English (en)' );
					expect( senseValues.glosses[ 0 ].value ).to.eq( 'cats' );
				} );

				// Edit and submit with unnecessary whitespace
				sensesSection.setSenseInputFormValue( '   goats   ' );
				sensesSection.submitNthSense( 0 );

				// Confirm that whitespace is trimmed
				sensesSection.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'goats' );
			} );
		}
	);

	it(
		'Gloss value is unchanged after editing was cancelled. ' +
			'It cannot save with a redundant language, but can with another language. ' +
			'A Gloss can be removed, and the whole sense can be removed',
		() => {
			cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
				lexemePage.open( lexemeId );

				// Edit, cancel the editing, and confirm no change persists
				sensesSection.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'cats' );
				sensesSection.editSenseNoSubmit( 0, 'goats' );
				sensesSection.cancelSenseEditing( 0 );
				sensesSection.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'cats' );

				// Confirm that a gloss with a redundant language cannot be added
				sensesSection.addGlossToNthSense( 0, 'english two', 'en', false );
				sensesSection.isNthSenseSubmittable( 0 ).its( 'submittable' ).should( 'be.false' );
				sensesSection.cancelSenseEditing( 0 );

				// Add a gloss in a non-redundant language
				sensesSection.addGlossToNthSense( 0, 'andere Sprache', 'de', true );
				sensesSection.startEditingNthSense( 0 );
				sensesSection.getNthSenseFormValues( 0 ).then( ( senseValues ) => {
					expect( senseValues.glosses.length ).to.eq( 2 );
				} );

				// Remove one gloss and confirm the other remains
				sensesSection.removeGloss( 0, true );
				sensesSection.getNthSenseFormValues( 0 ).then( ( senseValues ) => {
					expect( senseValues.glosses.length ).to.eq( 1 );
				} );

				// Remove the sense
				sensesSection.startEditingNthSense( 0 );
				sensesSection.removeSense( 0 );
				sensesSection.getSenses().should( 'not.exist' );
			} );
		}
	);
} );

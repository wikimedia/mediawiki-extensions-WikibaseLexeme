import { LexemePage } from '../support/pageObjects/LexemePage';
import { SensesPage } from '../support/pageObjects/SensesPage';

const lexemePage = new LexemePage();
const sensesPage = new SensesPage();

describe( 'Lexeme:Senses', () => {

	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' ).as( 'lexemeId' );
	} );

	it( 'can edit sense and save successfully', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			cy.task( 'MwLexemeApi:AddSense', { lexemeId, senseData: {
				glosses: {
					en: { language: 'en', value: 'cats' }
				}
			} } );
			lexemePage.open( lexemeId );
			sensesPage.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'cats' );

			sensesPage.editSenseValueAndSubmit( 0, 'goats' );

			sensesPage.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'goats' );
		} );
	} );

	it( 'can not save senses with redundant languages', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			cy.task( 'MwLexemeApi:AddSense', { lexemeId, senseData: {
				glosses: {
					en: { language: 'en', value: 'one' }
				}
			} } );

			lexemePage.open( lexemeId );
			sensesPage.addGlossToNthSense( 0, 'two', 'en', false );

			sensesPage.isNthSenseSubmittable( 0 ).its( 'submittable' ).should( 'be.false' );
		} );
	} );

	it( 'shows the language and value in edit mode', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			cy.task( 'MwLexemeApi:AddSense', { lexemeId, senseData: {
				glosses: {
					en: { language: 'en', value: 'goat' }
				}
			} } );
			lexemePage.open( lexemeId );

			sensesPage.startEditingNthSense( 0 );
			sensesPage.getNthSenseFormValues( 0 ).then( ( senseValues ) => {
				expect( senseValues.glosses[ 0 ].language ).to.eq( 'English (en)' );
				expect( senseValues.glosses[ 0 ].value ).to.eq( 'goat' );
			} );
		} );
	} );

	it( 'removes sense when clicked on remove', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			cy.task( 'MwLexemeApi:AddSense', { lexemeId, senseData: {
				glosses: {
					en: { language: 'en', value: 'goat' }
				}
			} } );
			lexemePage.open( lexemeId );

			sensesPage.startEditingNthSense( 0 );
			sensesPage.removeSense( 0 );

			sensesPage.getSenses().should( 'not.exist' );
		} );
	} );

	it( 'Gloss value unchanged after editing was cancelled', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			cy.task( 'MwLexemeApi:AddSense', { lexemeId, senseData: {
				glosses: {
					en: { language: 'en', value: 'goat' }
				}
			} } );
			lexemePage.open( lexemeId );

			sensesPage.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'goat' );

			sensesPage.editSenseNoSubmit( 0, 'goats' );
			sensesPage.cancelSenseEditing( 0 );

			sensesPage.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'goat' );
		} );
	} );

	it( 'Removes Gloss', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			cy.task( 'MwLexemeApi:AddSense', { lexemeId, senseData: {
				glosses: {
					en: { language: 'en', value: 'goat' }
				}
			} } );
			lexemePage.open( lexemeId );
			sensesPage.addGlossToNthSense( 0, 'test', 'de', true );
			sensesPage.startEditingNthSense( 0 );
			sensesPage.getNthSenseFormValues( 0 ).then( ( senseValues ) => {
				expect( senseValues.glosses.length ).to.eq( 2 );
			} );
			sensesPage.removeGloss( 0, true );
			sensesPage.getNthSenseFormValues( 0 ).then( ( senseValues ) => {
				expect( senseValues.glosses.length ).to.eq( 1 );
			} );
		} );
	} );

	it( 'Trims whitespace from Gloss', () => {
		cy.getStringAlias( '@lexemeId' ).then( ( lexemeId: string ) => {
			cy.task( 'MwLexemeApi:AddSense', { lexemeId, senseData: {
				glosses: {
					en: { language: 'en', value: 'cat animal' }
				}
			} } );

			lexemePage.open( lexemeId );
			sensesPage.editSenseValueAndSubmit( 0, 'cat ' );

			sensesPage.getNthSenseData( 0 ).its( 'value' ).should( 'eq', 'cat' );
			cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } ).then( ( lexeme ) => {
				expect( lexeme.senses[ 0 ].glosses.en.value ).to.eq( 'cat' );
			} );
		} );
	} );

} );

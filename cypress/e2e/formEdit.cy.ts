import { Util } from 'cypress-wikibase-api';

import { LexemePage } from '../support/pageObjects/LexemePage';
import { FormsSection } from '../support/pageObjects/FormsSection';

const lexemePage = new LexemePage();
const formsSection = new FormsSection();

describe( 'Lexeme:Forms', () => {

	it( 'can add, edit and remove representation. it ' +
		'cannot save representations with duplicate languages, and trims ' +
		'whitespace from representation. Grammatical features can be added ' +
		' and removed.', () => {
		cy.task( 'MwLexemeApi:CreateLexemeWithForm', {
			'en-ca': { language: 'en-ca', value: 'color' }
		} ).then( ( formAndLexeme ) => {
			lexemePage.open( formAndLexeme.lexemeId );

			// Add a representation to the Form
			formsSection.addRepresentationToForm( formAndLexeme.formId, 'colour', 'en-gb' );
			cy.task( 'MwApi:GetEntityData', { entityId: formAndLexeme.lexemeId } )
				.should( ( lexemeObject ) => {
					expect( Object.keys( lexemeObject.forms ).length ).to.eq( 1 );
					expect( lexemeObject.forms[ 0 ].representations[ 'en-ca' ].value )
						.to.eq( 'color' );
					expect( lexemeObject.forms[ 0 ].representations[ 'en-gb' ].value )
						.to.eq( 'colour' );
				} );

			// Edit the representation - check whitespace is trimmed
			formsSection.editRepresentationOfForm( formAndLexeme.formId, 'couleur ', 'fr' );
			cy.task( 'MwApi:GetEntityData', { entityId: formAndLexeme.lexemeId } )
				.should( ( lexemeObject ) => {
					expect( Object.keys( lexemeObject.forms ).length ).to.eq( 1 );
					expect( lexemeObject.forms[ 0 ].representations[ 'en-ca' ].value )
						.to.eq( 'color' );
					expect( lexemeObject.forms[ 0 ].representations.fr.value )
						.to.eq( 'couleur' );
				} );
			formsSection.getNthFormLastRepresentationData( 0 )
				.its( 'value' ).should( 'eq', 'couleur' );

			// Remove the representation
			formsSection.removeLastRepresentationOfForm( formAndLexeme.formId );
			cy.task( 'MwApi:GetEntityData', { entityId: formAndLexeme.lexemeId } )
				.should( ( lexemeObject ) => {
					expect( Object.keys( lexemeObject.forms ).length ).to.eq( 1 );
					expect( lexemeObject.forms[ 0 ].representations[ 'en-ca' ].value )
						.to.eq( 'color' );
					expect( lexemeObject.forms[ 0 ].representations )
						.not.to.have.any.keys( 'fr' );
				} );

			// Attempt to add a representation with an existing language
			formsSection.addRepresentationToForm( formAndLexeme.formId, 'colour', 'en-ca', false );
			formsSection.editRepresentationFormHasInvalidLanguageInput( formAndLexeme.formId );
			formsSection.cancelAddForm();

			// Test grammatical features
			cy.task( 'MwApi:CreateItem', { label: Util.getTestString( 'grammaticalItem' ) } )
				.then( ( grammaticalFeatureId: string ) => {
					// Add grammatical feature
					formsSection.addGrammaticalFeatureToForm(
						formAndLexeme.formId,
						grammaticalFeatureId
					);
					cy.task( 'MwApi:GetEntityData', { entityId: formAndLexeme.lexemeId } )
						.should( ( lexemeObject ) => {
							expect( lexemeObject.forms[ 0 ].grammaticalFeatures.length )
								.to.eq( 1 );
							expect( lexemeObject.forms[ 0 ].grammaticalFeatures[ 0 ] )
								.to.eq( grammaticalFeatureId );
						} );

					// Remove first (only) grammatical feature
					formsSection.removeFirstGrammaticalFeatureFromForm( formAndLexeme.formId );
					cy.task( 'MwApi:GetEntityData', { entityId: formAndLexeme.lexemeId } )
						.should( ( lexemeObject ) => {
							expect( lexemeObject.forms[ 0 ].grammaticalFeatures.length ).to.eq( 0 );
						} );
				} );
		} );
	} );

	describe( 'FormId generation', () => {

		it( 'FormId counter is not decremented when addForm is undone', () => {
			cy.task( 'MwLexemeApi:CreateLexeme' ).then( ( lexemeId: string ) => {
				lexemePage.open( lexemeId );

				formsSection.addForm( 'en', 'Foo' );
				formsSection.getFormId().then( ( formId ) => {
					const oldFormID = parseInt( formId.text().split( '-F' )[ 1 ] );
					lexemePage.undoLatestRevision();
					formsSection.addForm( 'de', 'Yacht' );
					formsSection.getFormId().then( ( newFormIdElement ) => {
						const newFormId = parseInt( newFormIdElement.text().split( '-F' )[ 1 ] );
						expect( newFormId ).to.be.greaterThan( oldFormID );
					} );
				} );
			} );
		} );

		it( 'FormId counter is not decremented when old revision is restored', () => {
			cy.task( 'MwLexemeApi:CreateLexeme' ).then( ( lexemeId: string ) => {
				lexemePage.open( lexemeId );

				formsSection.addForm( 'en', 'Foo' );
				formsSection.getFormId().then( ( formId ) => {
					const oldFormID = parseInt( formId.text().split( '-F' )[ 1 ] );
					lexemePage.restorePreviousRevision();
					formsSection.addForm( 'de', 'Yacht' );
					formsSection.getFormId().then( ( newFormIdElement ) => {
						const newFormId = parseInt( newFormIdElement.text().split( '-F' )[ 1 ] );
						expect( newFormId ).to.be.greaterThan( oldFormID );
					} );
				} );
			} );
		} );

	} );

	it( 'has statement list and can edit statements on a new Form', () => {
		cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
			.then( ( propertyId: string ) => {
				cy.task( 'MwLexemeApi:CreateLexeme' ).then( ( lexemeId: string ) => {
					lexemePage.open( lexemeId );

					formsSection.addForm( 'en', 'newForm' );
					formsSection.getFormId().then( ( formIdElement ) => {
						const formId = formIdElement.text();

						// Check statement list exists
						formsSection.getFormStatementList( formId );

						// Add a statement to the form
						const statementValue = 'Some string';
						formsSection.addStatementToForm(
							formIdElement.text(),
							propertyId,
							statementValue
						);

						formsSection.getFormStatement( formId ).then( ( statementObject ) => {
							expect( statementObject.value ).to.eq( statementValue );
							expect( statementObject.propertyId[ 1 ] ).to.eq( propertyId );
						} );
					} );
				} );
			} );
	} );

} );

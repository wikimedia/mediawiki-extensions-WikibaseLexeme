import { LexemePage } from '../support/pageObjects/LexemePage';

const lexemePage = new LexemePage();

describe( 'Form:Section', () => {
	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme', {
			lemmas: {
				en: {
					value: 'section',
					language: 'en'
				}
			}
		} ).then( ( lexemeId ) => {
			cy.wrap( lexemeId ).as( 'lexemeId' );
			cy.task( 'MwLexemeApi:AddForm', {
				lexemeId: lexemeId,
				representations: {
					en: {
						language: 'en',
						value: 'sections'
					}
				}
			} ).then( ( formId ) => cy.wrap( formId ).as( 'formId' ) );
			lexemePage.open( lexemeId );
		} );
	} );

	it( 'Forms section exists with a header', () => {
		lexemePage.getFormsContainer();
		lexemePage.getFormsHeader();
	} );

	it( 'a form has an ID, representation, language, and grammatical features', () => {
		cy.get( '@formId' ).then( ( formId ) => {
			lexemePage.getFormId( formId ).should( 'have.text', formId );
			lexemePage.getRepresentationWidget( formId );
			lexemePage.getRepresentationLanguage( formId ).should( 'have.text', 'en' );
			lexemePage.getGrammaticalFeatureList( formId );
		} );
	} );
} );

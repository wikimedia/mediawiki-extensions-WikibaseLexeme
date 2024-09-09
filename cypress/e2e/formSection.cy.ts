import { LexemePage } from '../support/pageObjects/LexemePage';
import { FormsSection } from '../support/pageObjects/FormsSection';

const lexemePage = new LexemePage();
const formsSection = new FormsSection();

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
		formsSection.getFormsContainer();
		formsSection.getFormsHeader();
	} );

	it( 'a form has an ID, representation, language, and grammatical features', () => {
		cy.getStringAlias( '@formId' ).then( ( formId ) => {
			formsSection.getFormId( formId ).should( 'have.text', formId );
			formsSection.getRepresentationWidget( formId );
			formsSection.getRepresentationLanguage( formId ).should( 'have.text', 'en' );
			formsSection.getGrammaticalFeatureList( formId );
		} );
	} );
} );

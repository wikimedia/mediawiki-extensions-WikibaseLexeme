import { LexemePage } from '../support/pageObjects/LexemePage';
import { FormsSection } from '../support/pageObjects/FormsSection';

const lexemePage = new LexemePage();
const formsSection = new FormsSection();

describe( 'LexemePage', () => {
	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme', {
			lemmas: {
				en: {
					value: 'section',
					language: 'en'
				}
			}
		} ).as( 'lexemeId' )
			.then( ( lexemeId: string ) => {
				cy.task( 'MwLexemeApi:AddForm', {
					lexemeId: lexemeId,
					representations: {
						en: {
							language: 'en',
							value: 'sections'
						}
					}
				} ).as( 'formId' );
				lexemePage.open( lexemeId );
			} );
	} );

	it(
		'Lexeme Header exists and includes the lexeme id. ' +
		'Forms section exists. ' +
		'A form has an ID, representation, language, and grammatical features',
		() => {
			cy.getStringAlias( '@lexemeId' ).then( ( lexemeId ) => {
				lexemePage.getHeaderId().should( 'eq', lexemeId );
			} );

			formsSection.getFormsContainer();
			formsSection.getFormsHeader();

			cy.getStringAlias( '@formId' ).then( ( formId ) => {
				formsSection.getFormId( formId ).should( 'have.text', formId );
				formsSection.getRepresentationWidget( formId );
				formsSection.getRepresentationLanguage( formId ).should( 'have.text', 'en' );
				formsSection.getGrammaticalFeatureList( formId );
			} );
		}
	);
} );

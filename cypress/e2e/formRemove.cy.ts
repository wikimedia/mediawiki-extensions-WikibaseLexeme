import { LexemePage } from '../support/pageObjects/LexemePage';
import { FormsSection } from '../support/pageObjects/FormsSection';

const lexemePage = new LexemePage();
const formsSection = new FormsSection();

describe( 'Lexeme:Forms', () => {
	it( 'can be removed', () => {
		cy.task( 'MwLexemeApi:CreateLexeme', {
			lemmas: {
				en: {
					value: 'remove',
					language: 'en'
				}
			}
		} ).then( ( lexemeId: string ) => {
			cy.task( 'MwLexemeApi:AddForm', {
				lexemeId: lexemeId,
				representations: {
					en: {
						language: 'en',
						value: 'remoooove'
					}
				}
			} ).then( ( formId: string ) => {
				lexemePage.open( lexemeId );
				formsSection.removeForm( formId );

				cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
					.then( ( lexeme ) => {
						cy.wrap( lexeme.forms )
							.should( 'have.length', 0 );
					} );
			} );
		} );
	} );
} );

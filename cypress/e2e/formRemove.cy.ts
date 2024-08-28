import { LexemePage } from '../support/pageObjects/LexemePage';

const lexemePage = new LexemePage();

describe( 'Lexeme:Forms', () => {
	it( 'can be removed', () => {
		cy.task( 'MwLexemeApi:CreateLexeme', {
			lemmas: {
				en: {
					value: 'remove',
					language: 'en'
				}
			}
		} ).then( ( lexemeId ) => {
			cy.task( 'MwLexemeApi:AddForm', {
				lexemeId: lexemeId,
				representations: {
					en: {
						language: 'en',
						value: 'remoooove'
					}
				}
			} ).then( ( formId ) => {
				lexemePage.open( lexemeId );
				lexemePage.removeForm( formId );

				cy.task( 'MwApi:GetEntityData', { entityId: lexemeId } )
					.then( ( lexeme ) => {
						cy.wrap( lexeme.forms )
							.should( 'have.length', 0 );
					} );
			} );
		} );
	} );
} );

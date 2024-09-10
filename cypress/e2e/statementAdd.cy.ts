import { LexemePage } from '../support/pageObjects/LexemePage';
import { Util } from 'cypress-wikibase-api';

const lexemePage = new LexemePage();

describe( 'Lexeme:Statements', () => {
	beforeEach( () => {
		cy.task( 'MwLexemeApi:CreateLexeme' )
			.then( ( lexemeId ) => {
				lexemePage.open( lexemeId );
				cy.wrap( lexemeId ).as( 'lexemeId' );
			} );

		cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
			.as( 'propertyId' );
	} );

	it( 'can be added', () => {
		cy.getStringAlias( '@propertyId' ).then( ( propertyId ) => {
			const testStringValue = Util.getTestString();
			lexemePage.addMainStatement( propertyId, testStringValue );

			cy.get( '#' + propertyId );
			lexemePage.getStatementValueElement().should( 'have.text', testStringValue );

			cy.getStringAlias( '@lexemeId' ).then( ( lexemeId ) => {
				cy.task( 'MwApi:GetEntityData', {
					entityId: lexemeId
				} ).then( ( lexemeObject ) => {
					cy.wrap( lexemeObject.claims[ propertyId ] )
						.should( 'have.length', 1 );
					cy.wrap( lexemeObject.claims[ propertyId ][ 0 ].mainsnak.datavalue.value )
						.should( 'eq', testStringValue );
				} );
			} );
		} );
	} );
} );

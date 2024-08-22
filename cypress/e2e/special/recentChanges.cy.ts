import { SpecialRecentChanges } from '../../support/pageObjects/SpecialRecentChanges';

const specialRecentChanges = new SpecialRecentChanges();

describe( 'Special:RecentChanges', () => {

	it( 'shows lemmas in title links to lexemes on Special:RecentChanges', () => {
		cy.task( 'MwLexemeApi:CreateLexeme', { lemmas: {
			ruq: {
				value: 'entrôpi',
				language: 'ruq'
			},
			'ruq-latn': {
				value: 'entropy',
				language: 'ruq-latn'
			},
			'ruq-cyrl': {
				value: 'ентропы',
				language: 'ruq-cyrl'
			}
		}
		} ).then( ( lemmaId ) => {
			specialRecentChanges.open();

			specialRecentChanges.getLastLexeme().then( ( lexeme ) => {
				const title = lexeme.text();
				expect( title ).to.include( 'entrôpi' );
				expect( title ).to.include( 'entropy' );
				expect( title ).to.include( 'ентропы' );
				expect( title ).to.include( lemmaId );
			} );
		} );
	} );

} );

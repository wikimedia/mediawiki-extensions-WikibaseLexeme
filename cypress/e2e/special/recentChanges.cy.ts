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
		} ).then( ( lexemeId ) => {
			specialRecentChanges.open();

			specialRecentChanges.getRecentLexemeChanges()
				.contains( lexemeId )
				.should( ( titleElement ) => {
					expect( titleElement ).to.contain( 'entrôpi' );
					expect( titleElement ).to.contain( 'entropy' );
					expect( titleElement ).to.contain( 'ентропы' );
				} );
		} );
	} );

} );

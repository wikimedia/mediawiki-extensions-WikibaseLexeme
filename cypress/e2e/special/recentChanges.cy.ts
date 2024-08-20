import { SpecialRecentChanges } from '../../support/pageObjects/SpecialRecentChanges';

import { Util } from 'cypress-wikibase-api';

const specialRecentChanges = new SpecialRecentChanges();

describe( 'Special:RecentChanges', () => {

	it( 'shows lemmas in title links to lexemes on Special:RecentChanges', () => {
		const lemma = Util.getTestString( 'lemma-' ),
			language = Util.getTestString( 'language-' ),
			lexicalCategory = Util.getTestString( 'lexicalCategory-' );

		cy.task( 'MwApi:CreateItem', { label: language } ).then( ( languageId ) => {
			cy.task( 'MwApi:CreateItem', { label: lexicalCategory } ).then( ( lexicalCategoryId ) => {
				const data = {
					lexicalCategory: lexicalCategoryId,
					language: languageId,
					lemmas: {
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
				};
				cy.task( 'MwApi:CreateEntity', { entityType: 'lexeme', label: lemma, data: data } ).then( ( lemmaId ) => {
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
	} );

} );

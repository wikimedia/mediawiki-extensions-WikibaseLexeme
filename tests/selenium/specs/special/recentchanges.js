'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../../lexeme.api' ),
	RecentChangesPage = require( '../../../../../../tests/selenium/pageobjects/recentchanges.page' );

describe( 'Special:RecentChanges', () => {

	it( 'shows lemmas in title links to lexemes on Special:RecentChanges', () => {
		const id = browser.call( () => LexemeApi.create( {
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
		} ).then( ( lexeme ) => lexeme.id ) );

		RecentChangesPage.open();

		const title = RecentChangesPage.titles[ 0 ].getText();

		assert( title.includes( 'entrôpi' ) );
		assert( title.includes( 'entropy' ) );
		assert( title.includes( 'ентропы' ) );
		assert( title.includes( id ) );
	} );

} );

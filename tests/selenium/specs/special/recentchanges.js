'use strict';

const assert = require( 'assert' ),
	LexemeApi = require( '../../lexeme.api' ),
	RecentChangesPage = require( '../../pageobjects/recentchanges.page' );

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

		browser.pause( 2000 );

		RecentChangesPage.open();

		const title = RecentChangesPage.lastLexeme.getText();

		assert( title.includes( 'entrôpi' ) );
		assert( title.includes( 'entropy' ) );
		assert( title.includes( 'ентропы' ) );
		assert( title.includes( id ) );
	} );

} );

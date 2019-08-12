'use strict';

const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../../lexeme.api' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	WatchlistPage = require( '../../../../../../tests/selenium/pageobjects/watchlist.page' ),
	WatchablePage = require( '../../../../../../tests/selenium/pageobjects/watchable.page' );

describe( 'Special:Watchlist', () => {

	let username, password;

	before( function () {
		username = Util.getTestString( 'user-' );
		password = Util.getTestString( 'password-' );

		browser.call( () => Api.createAccount( username, password ) );
	} );

	beforeEach( function () {
		browser.deleteCookie();
		LoginPage.login( username, password );
	} );

	it( 'shows lemmas in title links to lexemes', () => {
		const id = browser.call( () => LexemeApi.create( {
			lemmas: {
				en: {
					value: 'color',
					language: 'en'
				},
				'en-gb': {
					value: 'colour',
					language: 'en-gb'
				}
			}
		} ).then( ( lexeme ) => lexeme.id ) );

		WatchablePage.watch( 'Lexeme:' + id );

		WatchlistPage.open();
		const title = WatchlistPage.titles[ 0 ].getText();

		assert( title.includes( 'color' ) );
		assert( title.includes( 'colour' ) );
		assert( title.includes( id ) );
	} );

} );

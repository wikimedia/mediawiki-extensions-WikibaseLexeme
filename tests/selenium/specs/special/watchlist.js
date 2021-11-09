'use strict';

const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	Util = require( 'wdio-mediawiki/Util' ),
	LexemeApi = require( '../../lexeme.api' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	LogoutPage = require( '../../pageobjects/logout.page' ),
	WatchlistPage = require( '../../pageobjects/watchlist.page' ),
	WatchablePage = require( '../../pageobjects/watchable.page' );

describe( 'Special:Watchlist', () => {

	let username, password, bot;

	before( async () => {
		username = Util.getTestString( 'user-' );
		password = Util.getTestString( 'password-' );
		bot = await Api.bot();
		await Api.createAccount( bot, username, password );
	} );

	it( 'shows lemmas in title links to lexemes on Special:Watchlist', () => {
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

		LogoutPage.ensureLoggedOut();
		LoginPage.login( username, password );

		WatchablePage.watch( 'Lexeme:' + id );

		WatchlistPage.open();
		const title = WatchlistPage.titles[ 0 ].getText();

		assert( title.includes( 'color' ) );
		assert( title.includes( 'colour' ) );
		assert( title.includes( id ) );
	} );

} );

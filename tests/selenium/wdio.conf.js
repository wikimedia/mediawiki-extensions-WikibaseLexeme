'use strict';

const { config } = require( 'wdio-mediawiki/wdio-defaults.conf.js' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	LexemeApi = require( './lexeme.api' ),
	Replication = require( './replication' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

exports.config = { ...config,
	// Override, or add to, the setting from wdio-mediawiki.
	// Learn more at https://webdriver.io/docs/configurationfile/
	//
	// Example:
	// logLevel: 'info',
	specs: [
		__dirname + '/specs/*.js',
		__dirname + '/specs/special/*.js'
	],

	// Default timeout for all waitFor* commands.
	waitforTimeout: 20000,

	// custom config to be used for waitFor* timeouts where we're not waiting for an API call or such
	nonApiTimeout: 10000,

	startOfTestTime: null,

	before: function ( capabilities, specs, browser ) {
		browser.log = function ( message ) {
			console.log( `${Date.now() - browser.config.startOfTestTime}: ${message}` );
		};

		browser.clickTillItExists = function ( clickTarget, targetToExist, timeoutMsg ) {
			browser.waitUntil(
				() => {
					clickTarget.click();

					return targetToExist.isExisting();
				},
				{
					timeout: 5000,
					interval: 500,
					timeoutMsg
				}
			);
		};
	},

	beforeTest: function ( test, context ) {
		config.beforeTest( test, context );
		browser.config.startOfTestTime = Date.now();
	},

	beforeSuite: function () {
		LoginPage.loginAdmin();
		browser.executeAsync( function ( done ) {
			/* global mw */
			// save any option (setting it to its previous value),
			// to make a database write and get a chronology protector cookie
			mw.loader.using( 'mediawiki.user' ).then( function () {
				new mw.Api().saveOption(
					'gender',
					mw.user.options.get( 'gender', 'unknown' )
				).then( done );
			} );
		} );
		// pass the chronology protector cookie into LexemeApi and WikibaseApi,
		// where it’s used for all mwbot requests as well
		const cookie = browser.getCookies( [ 'cpPosIndex' ] )[ 0 ];
		browser.call( () => LexemeApi.initialize( cookie && cookie.value ) );
		browser.call( () => WikibaseApi.initialize( cookie && cookie.value ) );
		browser.config.defaultLexicalCategory = WikibaseApi.createItem();
		browser.config.defaultLanguage = WikibaseApi.createItem();
		Replication.waitForReplicationLag( LexemeApi.getBot() );
	}

};

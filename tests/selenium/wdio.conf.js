'use strict';

const { config } = require( 'wdio-mediawiki/wdio-defaults.conf.js' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	LexemeApi = require( './lexeme.api' );

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
		// pass the chronology protector cookie into LexemeApi,
		// where it’s used for all mwbot requests as well
		const cookie = browser.getCookies( [ 'cpPosIndex' ] )[ 0 ];
		browser.call( () => LexemeApi.initialize( cookie.value ) );
	}

};

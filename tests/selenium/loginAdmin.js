const LoginPage = require( 'wdio-mediawiki/LoginPage' );

/**
 * TODO use LoginPage.loginAdmin() compatible w/ wdio 5 from wdio-mediawiki v1.0.0+
 */
module.exports = function () {
	LoginPage.open();
	$( '#wpName1' ).setValue( browser.config.username );
	$( '#wpPassword1' ).setValue( browser.config.password );
	$( '#wpLoginAttempt' ).click(); // eslint-disable-line no-jquery/no-event-shorthand
};

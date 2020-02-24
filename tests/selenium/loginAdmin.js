const LoginPage = require( 'wdio-mediawiki/LoginPage' );

/**
 * TODO use LoginPage.loginAdmin() compatible w/ wdio 5 from wdio-mediawiki v1.0.0+
 */
module.exports = function () {
	LoginPage.open();
	$( '#wpName1' ).setValue( browser.config.mwUser );
	$( '#wpPassword1' ).setValue( browser.config.mwPwd );
	$( '#wpLoginAttempt' ).click(); // eslint-disable-line no-jquery/no-event-shorthand
};

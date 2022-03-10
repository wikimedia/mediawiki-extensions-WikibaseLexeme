( function () {
	var init = require( './new-lexeme-dist/SpecialNewLexeme.cjs.js' );
	var config = require( './licenseConfig.json' );

	init(
		{
			rootSelector: '#special-newlexeme-root',
			token: mw.user.tokens.get( 'csrfToken' ),
			licenseUrl: config.licenseUrl,
			licenseName: config.licenseText
		},
		mw
	);
}() );

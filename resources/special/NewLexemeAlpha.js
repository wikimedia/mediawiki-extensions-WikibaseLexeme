( function () {
	var init = require( './new-lexeme-dist/SpecialNewLexeme.cjs.js' );
	var config = require( './licenseConfig.json' );

	init(
		{
			rootSelector: '#special-newlexeme-root',
			licenseUrl: config.licenseUrl,
			licenseName: config.licenseText
		},
		mw
	);
}() );

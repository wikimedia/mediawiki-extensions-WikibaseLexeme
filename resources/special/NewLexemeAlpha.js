( function () {
	var init = require( './new-lexeme-dist/SpecialNewLexeme.cjs.js' );
	var settings = require( './settings.json' );

	init(
		{
			rootSelector: '#special-newlexeme-root',
			licenseUrl: settings.licenseUrl,
			licenseName: settings.licenseText,
			tags: settings.tags,
			wikibaseLexemeTermLanguages: settings.wikibaseLexemeTermLanguages
		},
		mw
	);
}() );

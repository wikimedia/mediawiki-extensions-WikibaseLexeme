( function () {
	var init = require( './new-lexeme-dist/SpecialNewLexeme.cjs.js' );
	var settings = require( './settings.json' );
	var languageNames = require( './languageNames.json' );

	// eslint-disable-next-line no-undef
	var languageNamesMap = new Map();
	for ( var languageName in languageNames ) {
		languageNamesMap.set( languageName, languageNames[ languageName ] );
	}

	init(
		{
			rootSelector: '#special-newlexeme-root',
			licenseUrl: settings.licenseUrl,
			licenseName: settings.licenseText,
			tags: settings.tags,
			wikibaseLexemeTermLanguages: languageNamesMap
		},
		mw
	);
}() );

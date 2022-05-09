( function () {
	var init = require( './new-lexeme-dist/SpecialNewLexeme.cjs.js' );
	var settings = require( './settings.json' );
	var languageNames = require( './languageNames.json' );

	// eslint-disable-next-line no-undef
	var languageNamesMap = new Map();
	for ( var languageName in languageNames ) {
		languageNamesMap.set( languageName, languageNames[ languageName ] );
	}

	// remove server-rendered "search existing" link now that we're ready to render it in Vue
	document.getElementById( 'wbl-snl-intro-text-wrapper' ).textContent = '';

	init(
		{
			rootSelector: '#special-newlexeme-root',
			licenseUrl: settings.licenseUrl,
			licenseName: settings.licenseText,
			tags: settings.tags,
			wikibaseLexemeTermLanguages: languageNamesMap,
			lexicalCategorySuggestions: mw.config.get( 'wblSpecialNewLexemeLexicalCategorySuggestions' )
		},
		mw
	);
}() );

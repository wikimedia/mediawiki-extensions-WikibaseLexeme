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

	// includes labels, descriptions and language code statement value of referenced items
	var initParamsFromUrl = mw.config.get( 'wblSpecialNewLexemeParams' );

	var placeholderExampleData = mw.config.get( 'wblSpecialNewLexemeExampleData' );

	init(
		{
			rootSelector: '#special-newlexeme-root',
			isAnonymous: mw.user.isAnon(),
			licenseUrl: settings.licenseUrl,
			licenseName: settings.licenseText,
			tags: settings.tags,
			wikibaseLexemeTermLanguages: languageNamesMap,
			lexicalCategorySuggestions: mw.config.get( 'wblSpecialNewLexemeLexicalCategorySuggestions' ),
			initParams: initParamsFromUrl,
			placeholderExampleData: placeholderExampleData,
			maxLemmaLength: settings.maxLemmaLength,
			availableSearchProfiles: settings.availableSearchProfiles
		},
		mw
	);
}() );

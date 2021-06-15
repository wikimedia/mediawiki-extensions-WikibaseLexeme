( function ( wb ) {
	'use strict';

	var LanguageFromItemExtractor = require( '../services/LanguageFromItemExtractor.js' ),
		ItemLookup = require( '../services/ItemLookup.js' ),
		LexemeLanguageFieldObserver = require( './formHelpers/LexemeLanguageFieldObserver.js' ),
		repoConfig = mw.config.get( 'wbRepo' ),
		userLanguage = mw.config.get( 'wgUserLanguage' ),
		repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php',
		languageSelector = wb.lexeme.widgets.ItemSelectorWidget.static.infuse(
			$( '#wb-newlexeme-lexeme-language' )
		),
		lexicalCategorySelector = wb.lexeme.widgets.ItemSelectorWidget.static.infuse(
			$( '#wb-newlexeme-lexicalCategory' )
		),
		mwApi = wb.api.getLocationAgnosticMwApi( repoApiUrl ),
		$lemmaLanguageField = OO.ui.infuse(
			OO.ui.infuse( $( '#wb-newlexeme-lemma-language' ) ).$element.parents( '.oo-ui-fieldLayout' )
		).$element,
		itemSelectorConfig = {
			apiUrl: repoApiUrl,
			language: userLanguage,
			timeout: 8000
		};

	languageSelector.initialize( $.extend( {
		changeObserver: new LexemeLanguageFieldObserver(
			$lemmaLanguageField,
			new ItemLookup( new wb.api.RepoApi( mwApi, userLanguage ) ),
			new LanguageFromItemExtractor( mw.config.get( 'LexemeLanguageCodePropertyId' ) )
		)
	}, itemSelectorConfig ) );

	lexicalCategorySelector.initialize( itemSelectorConfig );

}( wikibase ) );

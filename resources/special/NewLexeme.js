( function ( wb ) {
	'use strict';

	var LanguageFromItemExtractor = require( '../services/LanguageFromItemExtractor.js' ),
		ItemLookup = require( '../services/ItemLookup.js' ),
		LexemeLanguageFieldObserver = require( '../special/formHelpers/LexemeLanguageFieldObserver.js' ),
		repoConfig = mw.config.get( 'wbRepo' ),
		repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php',
		languageSelector = wb.lexeme.widgets.ItemSelectorWidget.static.infuse(
			$( '#wb-newlexeme-lexeme-language' )
		),
		lexicalCategorySelector = wb.lexeme.widgets.ItemSelectorWidget.static.infuse(
			$( '#wb-newlexeme-lexicalCategory' )
		),
		mwApi = wb.api.getLocationAgnosticMwApi( repoApiUrl ),
		$lemmaLanguageField = OO.ui.infuse(
			OO.ui.infuse( $( '#wb-newlexeme-lemma-language' ) ).$element.parents( '.mw-htmlform-field-autoinfuse' )
		).$element,
		itemSelectorConfig = {
			apiUrl: repoApiUrl,
			language: mw.config.get( 'wgUserLanguage' ),
			timeout: 8000
		};

	languageSelector.initialize( $.extend( {
		changeObserver: new LexemeLanguageFieldObserver(
			$lemmaLanguageField,
			new ItemLookup( new wb.api.RepoApi( mwApi ) ),
			new LanguageFromItemExtractor( mw.config.get( 'LexemeLanguageCodePropertyId' ) )
		)
	}, itemSelectorConfig ) );

	lexicalCategorySelector.initialize( itemSelectorConfig );

}( wikibase ) );

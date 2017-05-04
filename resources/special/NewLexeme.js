( function ( $, mw, wb ) {
	'use strict';

	var repoConfig = mw.config.get( 'wbRepo' ),
		repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php',
		lookup = wb.lexeme.widgets.LanguageLookupWidget.static.infuse(
			'wb-newlexeme-lexeme-language'
		),
		mwApi = wb.api.getLocationAgnosticMwApi( repoApiUrl ),
		$lemmaLanguageField = OO.ui.infuse(
			OO.ui.infuse( 'wb-newlexeme-lemma-language' ).$element.parents( '.mw-htmlform-field-autoinfuse' )
		).$element;

	$lemmaLanguageField.hide();

	lookup.initialize( {
		apiUrl: repoApiUrl,
		language: mw.config.get( 'wgUserLanguage' ),
		timeout: 8000,
		changeObserver: new wb.lexeme.special.formHelpers.LexemeLanguageFieldObserver(
			$lemmaLanguageField,
			new wb.lexeme.services.ItemLookup( new wb.api.RepoApi( mwApi ) ),
			new wb.lexeme.services.LanguageFromItemExtractor( mw.config.get( 'LexemeLanguageCodePropertyId' ) )
		)
	} );

}(
	jQuery,
	mediaWiki,
	wikibase
) );

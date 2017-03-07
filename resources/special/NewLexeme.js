( function ( $, mw, wb ) {
	'use strict';

	var repoConfig = mw.config.get( 'wbRepo' ),
		repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php',
		lookup = wb.lexeme.widgets.LanguageLookupWidget.static.infuse(
			'wb-newlexeme-lexeme-language'
		);

	lookup.initialize( {
		apiUrl: repoApiUrl,
		language: mw.config.get( 'wgUserLanguage' ),
		timeout: 8000
	} );

}(
	jQuery,
	mediaWiki,
	wikibase
) );

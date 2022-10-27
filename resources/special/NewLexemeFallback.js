/**
 * Fallback code for browsers that support some js, but not es6, to at least get the no-js experience.
 */

( function () {
	var moduleState = mw.loader.getState( 'wikibase.lexeme.special.NewLexeme' );
	if ( moduleState === 'error' || moduleState === 'missing' || moduleState === null ) {
		document.querySelectorAll( '#mw-content-text noscript' ).forEach( function ( noscriptNode ) {
			noscriptNode.outerHTML = noscriptNode.textContent;
		} );
	}
}() );

var moduleState = mw.loader.getState( 'wikibase.lexeme.special.NewLexemeAlpha' );
if ( moduleState === 'error' || moduleState === 'missing' || moduleState === null ) {
	document.querySelectorAll( '#mw-content-text noscript' ).forEach( function ( noscriptNode ) {
		noscriptNode.outerHTML = noscriptNode.textContent;
	} );
}

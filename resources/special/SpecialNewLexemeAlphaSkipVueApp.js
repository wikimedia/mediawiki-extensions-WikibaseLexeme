var moduleState = mw.loader.getState( 'wikibase.lexeme.special.NewLexemeAlpha' );
if ( moduleState === 'error' || moduleState === 'missing' || moduleState === null ) {
	var noscript = document.querySelector('#wbl-snl-noscript');
	var editWarning = document.querySelector('#wbl-snl-noscript-warning');
	var noscriptWrapper = document.querySelector('#wbl-snl-noscript-wrapper');
	if ( editWarning !== null ) {
		editWarning.outerHTML = editWarning.textContent;
	}
	noscriptWrapper.outerHTML = noscriptWrapper.textContent;
	noscript.outerHTML = noscript.textContent;
}

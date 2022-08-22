if ( mw.loader.moduleRegistry['wikibase.lexeme.special.NewLexemeAlpha'] ) {
	return false;
}

var root = document.querySelector('#special-newlexeme-root');
var noscript = document.querySelector('#special-newlexeme-noscript');
root.innerHTML = noscript.textContent;
return true;

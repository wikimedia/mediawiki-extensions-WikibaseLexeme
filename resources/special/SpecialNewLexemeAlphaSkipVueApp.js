if ( mw.loader.moduleRegistry['wikibase.lexeme.special.NewLexemeAlpha'] ) {
	return false;
}

const root = document.querySelector('#special-newlexeme-root');
const noscript = document.querySelector('#special-newlexeme-noscript');
root.innerHTML = noscript.textContent;
return true;

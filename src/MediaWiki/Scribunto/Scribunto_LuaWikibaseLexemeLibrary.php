<?php

namespace Wikibase\Lexeme\MediaWiki\Scribunto;

use Scribunto_LuaLibraryBase;

/**
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLexemeLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * Register the mw.wikibase.lexeme.lua library.
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = [
			// no functions right now (check out the git history if you want to add some)
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.lexeme.lua', $lib, []
		);
	}

}

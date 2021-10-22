<?php

namespace Wikibase\Lexeme\MediaWiki\Scribunto;

/**
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLexemeEntityLexemeLibrary extends Scribunto_LuaWikibaseLexemeAbstractEntityLibrary {

	/**
	 * Register the mw.wikibase.lexeme.entity.lexeme.lua library.
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = [
			'addAllUsage' => [ $this, 'addAllUsage' ],
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.lexeme.entity.lexeme.lua', $lib, []
		);
	}

}

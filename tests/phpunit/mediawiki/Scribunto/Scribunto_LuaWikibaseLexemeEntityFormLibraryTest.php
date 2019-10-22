<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntityFormLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 *
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLexemeEntityFormLibraryTest
	extends Scribunto_LuaWikibaseLexemeLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLexemeEntityFormLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLexemeEntityFormLibraryTests'
				=> __DIR__ . '/LuaWikibaseLexemeEntityFormLibraryTests.lua',
		];
	}

}

<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntitySenseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 *
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLexemeEntitySenseLibraryTest
	extends Scribunto_LuaWikibaseLexemeLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLexemeEntitySenseLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLexemeEntitySenseLibraryTests'
				=> __DIR__ . '/LuaWikibaseLexemeEntitySenseLibraryTests.lua',
		];
	}

}

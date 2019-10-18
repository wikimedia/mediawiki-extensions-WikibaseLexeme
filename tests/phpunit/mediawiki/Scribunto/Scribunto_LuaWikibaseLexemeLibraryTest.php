<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 *
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLexemeLibraryTest extends Scribunto_LuaWikibaseLexemeLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLexemeLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLexemeLibraryTests' => __DIR__ . '/LuaWikibaseLexemeLibraryTests.lua',
		];
	}

}

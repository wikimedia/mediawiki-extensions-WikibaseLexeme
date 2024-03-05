<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

use Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntityFormLibrary;

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

	public function testParserOutputUsageAccumulatorTracking() {
		$this->makeParserOutputUsageAccumulatorAssertions( Scribunto_LuaWikibaseLexemeEntityFormLibrary::class );
	}
}

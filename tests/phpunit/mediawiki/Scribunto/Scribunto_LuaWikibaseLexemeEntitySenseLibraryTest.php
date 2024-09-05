<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

use Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntitySenseLibrary;

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

	/** @var string */
	protected static $moduleName = 'LuaWikibaseLexemeEntitySenseLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLexemeEntitySenseLibraryTests'
				=> __DIR__ . '/LuaWikibaseLexemeEntitySenseLibraryTests.lua',
		];
	}

	public function testParserOutputUsageAccumulatorTracking() {
		$this->makeParserOutputUsageAccumulatorAssertions( Scribunto_LuaWikibaseLexemeEntitySenseLibrary::class );
	}
}

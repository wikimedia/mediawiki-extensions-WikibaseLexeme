<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

use Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntityLexemeLibrary;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntityLexemeLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 *
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLexemeEntityLexemeLibraryTest
	extends Scribunto_LuaWikibaseLexemeLibraryTestCase {

	/** @var string */
	protected static $moduleName = 'LuaWikibaseLexemeEntityLexemeLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLexemeEntityLexemeLibraryTests'
				=> __DIR__ . '/LuaWikibaseLexemeEntityLexemeLibraryTests.lua',
		];
	}

	public function testParserOutputUsageAccumulatorTracking() {
		$this->makeParserOutputUsageAccumulatorAssertions( Scribunto_LuaWikibaseLexemeEntityLexemeLibrary::class );
	}
}

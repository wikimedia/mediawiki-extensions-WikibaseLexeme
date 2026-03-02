<?php
declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntitySenseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group Database
 * @group Lua
 * @group LuaStandalone
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeEntitySenseLibraryStandaloneTest extends WikibaseLexemeEntitySenseLibraryTestBase {
	protected function getEngineName(): string {
		return 'LuaStandalone';
	}
}

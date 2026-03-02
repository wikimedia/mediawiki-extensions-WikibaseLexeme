<?php
declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group Database
 * @group Lua
 * @group LuaStandalone
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeLibraryStandaloneTest extends WikibaseLexemeLibraryTestBase {
	protected function getEngineName(): string {
		return 'LuaStandalone';
	}
}

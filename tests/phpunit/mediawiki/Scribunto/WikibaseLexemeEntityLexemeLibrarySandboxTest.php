<?php
declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Scribunto;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntityLexemeLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group Database
 * @group Lua
 * @group LuaSandbox
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeEntityLexemeLibrarySandboxTest extends WikibaseLexemeEntityLexemeLibraryTestBase {
	protected function getEngineName(): string {
		return 'LuaSandbox';
	}
}

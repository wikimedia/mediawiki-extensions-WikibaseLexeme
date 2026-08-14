<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWikiIntegrationTestCase;
use Wikibase\Lexeme\Registrar;

/**
 * @covers \Wikibase\Lexeme\Registrar
 * @group Wikibase
 * @group WikibaseLexeme
 * @license GPL-2.0-or-later
 */
class RegistrarTest extends MediaWikiIntegrationTestCase {

	public function testRegisterExtension_repoEnabled_registersRestRoutes() {
		$this->setMwGlobals( [
			'wgLexemeEnableRepo' => true,
			'wgRestAPIAdditionalRouteFiles' => [],
		] );

		Registrar::registerExtension();

		$this->assertContains(
			'extensions/WikibaseLexeme/src/MediaWiki/RestApi/routes.json',
			$GLOBALS['wgRestAPIAdditionalRouteFiles']
		);
	}

	public function testRegisterExtension_repoDisabled_doesNotRegisterRestRoutes() {
		$this->setMwGlobals( [
			'wgLexemeEnableRepo' => false,
			'wgRestAPIAdditionalRouteFiles' => [],
		] );

		Registrar::registerExtension();

		$this->assertSame( [], $GLOBALS['wgRestAPIAdditionalRouteFiles'] );
	}
}

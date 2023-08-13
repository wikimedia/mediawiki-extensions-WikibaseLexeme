<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Config;

use HashConfig;
use MediaWiki\ResourceLoader\Context;
use MediaWikiIntegrationTestCase;
use ResourceLoader;
use ResourceLoaderContext;
use WebRequest;
use Wikibase\Lexeme\MediaWiki\Config\LexemeLanguageCodePropertyIdConfig;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Config\LexemeLanguageCodePropertyIdConfig
 *
 * @license GPL-2.0-or-later
 */
class LexemeLanguageCodePropertyIdConfigTest extends MediaWikiIntegrationTestCase {

	private function newRLContext(): Context {
		return new ResourceLoaderContext(
			$this->createMock( ResourceLoader::class ),
			$this->createMock( WebRequest::class )
		);
	}

	public function testGetScript() {
		$module = new LexemeLanguageCodePropertyIdConfig();
		$module->setConfig( new HashConfig( [
			'LexemeLanguageCodePropertyId' => null,
		] ) );
		$script = $module->getScript( $this->newRLContext() );
		$this->assertStringStartsWith(
			'mw.config.set({"LexemeLanguageCodePropertyId":',
			$script
		);
		$this->assertStringEndsWith( '});', $script );
	}

	public function testEscapesConfigVariableContent() {
		$module = new LexemeLanguageCodePropertyIdConfig();
		$evilConfig = '"\'';
		$module->setConfig( new HashConfig( [
			'LexemeLanguageCodePropertyId' => $evilConfig,
		] ) );

		$this->assertStringContainsString(
			json_encode( $evilConfig ),
			$module->getScript( $this->newRLContext() )
		);
	}

}

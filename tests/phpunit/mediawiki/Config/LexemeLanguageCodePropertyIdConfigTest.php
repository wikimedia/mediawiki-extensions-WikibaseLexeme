<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Config;

use MediaWiki\Config\HashConfig;
use MediaWiki\Request\WebRequest;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWikiIntegrationTestCase;
use Wikibase\Lexeme\MediaWiki\Config\LexemeLanguageCodePropertyIdConfig;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Config\LexemeLanguageCodePropertyIdConfig
 *
 * @license GPL-2.0-or-later
 */
class LexemeLanguageCodePropertyIdConfigTest extends MediaWikiIntegrationTestCase {

	private function newRLContext(): Context {
		return new Context(
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

	public function testGetDefinitionSummary_notEqualForConfigValues(): void {
		$module1 = new LexemeLanguageCodePropertyIdConfig();
		$module1->setConfig( new HashConfig( [
			'LexemeLanguageCodePropertyId' => 'P1',
		] ) );

		$module2 = new LexemeLanguageCodePropertyIdConfig();
		$module2->setConfig( new HashConfig( [
			'LexemeLanguageCodePropertyId' => 'P2',
		] ) );

		$this->assertNotEquals(
			$module1->getDefinitionSummary( $this->newRLContext() ),
			$module2->getDefinitionSummary( $this->newRLContext() )
		);
	}

}

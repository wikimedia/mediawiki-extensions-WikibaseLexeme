<?php

namespace Wikibase\Lexeme\Tests\Config;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit\Framework\TestCase;
use ResourceLoaderContext;
use Wikibase\Lexeme\Config\LexemeLanguageCodePropertyIdConfig;

/**
 * @covers \Wikibase\Lexeme\Config\LexemeLanguageCodePropertyIdConfig
 *
 * @license GPL-2.0-or-later
 */
class LexemeLanguageCodePropertyIdConfigTest extends TestCase {

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|ResourceLoaderContext
	 */
	private function getContext() {
		return $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testGetScript() {
		$module = new LexemeLanguageCodePropertyIdConfig();
		$script = $module->getScript( $this->getContext() );
		$this->assertStringStartsWith(
			'mediaWiki.config.set( "LexemeLanguageCodePropertyId", ',
			$script
		);
		$this->assertStringEndsWith( ' );', $script );
	}

	public function testEscapesConfigVariableContent() {
		global $wgLexemeLanguageCodePropertyId;

		$module = new LexemeLanguageCodePropertyIdConfig();
		$evilConfig = '"\'';
		$wgLexemeLanguageCodePropertyId = $evilConfig;

		$this->assertContainsString(
			$module->getScript( $this->getContext() ),
			json_encode( $evilConfig )
		);
	}

	private function assertContainsString( $haystack, $needle ) {
		$this->assertTrue( strpos( $haystack, $needle ) !== false );
	}

}

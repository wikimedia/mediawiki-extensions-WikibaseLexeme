<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Config;

use MediaWikiTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use ResourceLoaderContext;
use Wikibase\Lexeme\MediaWiki\Config\LexemeLanguageCodePropertyIdConfig;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\Config\LexemeLanguageCodePropertyIdConfig
 *
 * @license GPL-2.0-or-later
 */
class LexemeLanguageCodePropertyIdConfigTest extends MediaWikiTestCase {

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
		$module = new LexemeLanguageCodePropertyIdConfig();
		$evilConfig = '"\'';
		$this->setMwGlobals( 'wgLexemeLanguageCodePropertyId', $evilConfig );

		$this->assertContainsString(
			$module->getScript( $this->getContext() ),
			json_encode( $evilConfig )
		);
	}

	private function assertContainsString( $haystack, $needle ) {
		$this->assertTrue( strpos( $haystack, $needle ) !== false );
	}

}

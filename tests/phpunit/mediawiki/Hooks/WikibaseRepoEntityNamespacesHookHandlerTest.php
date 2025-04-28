<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks;

use MediaWiki\Config\HashConfig;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Hooks\WikibaseRepoEntityNamespacesHookHandler;

/**
 * @covers Wikibase\Lexeme\Hooks\WikibaseRepoEntityNamespacesHookHandler
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class WikibaseRepoEntityNamespacesHookHandlerTest extends TestCase {

	public static function provideConfigurations(): iterable {
		yield 'with repo enabled' => [
			new HashConfig( [
				'LexemeEnableRepo' => true,
				'LexemeNamespace' => 1234,
			] ),
			[ 'lexeme' => 1234 ],
		];
		yield 'without repo enabled' => [
			new HashConfig( [
				'LexemeEnableRepo' => false,
				'LexemeNamespace' => 1234,
			] ),
			[],
		];
	}

	/** @dataProvider provideConfigurations */
	public function testOnWikibaseRepoEntityNamespaces( HashConfig $config, array $expected ) {
		$handler = new WikibaseRepoEntityNamespacesHookHandler( $config );
		$entityNamespaces = [];
		$handler->onWikibaseRepoEntityNamespaces( $entityNamespaces );

		$this->assertEquals( $expected, $entityNamespaces );
	}
}

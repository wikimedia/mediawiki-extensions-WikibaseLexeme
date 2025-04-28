<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks;

use MediaWiki\Config\HashConfig;
use Wikibase\Lexeme\Hooks\WikibaseRepoDataTypesHookHandler;

/**
 * @covers Wikibase\Lexeme\Hooks\WikibaseRepoDataTypesHookHandler
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class WikibaseRepoDataTypesHookHandlerTest extends \MediaWikiIntegrationTestCase {

	public static function provideConfigurations(): iterable {
		yield 'with repo enabled' => [
			new HashConfig( [
				'LexemeEnableRepo' => true,
			] ),
			[ 'PT:wikibase-lexeme', 'PT:wikibase-form', 'PT:wikibase-sense' ],
		];

		yield 'without repo enabled' => [
			new HashConfig( [
				'LexemeEnableRepo' => false,
			] ),
			[],
		];
	}

	/**
	 * @dataProvider provideConfigurations
	 */
	public function testOnWikibaseRepoDataTypes( $config, $expectedKeys ) {
		$dataTypeDefinitions = [];
		$handler = new WikibaseRepoDataTypesHookHandler( $config );
		$handler->onWikibaseRepoDataTypes( $dataTypeDefinitions );

		$this->assertEquals( $expectedKeys, array_keys( $dataTypeDefinitions ) );
	}
}

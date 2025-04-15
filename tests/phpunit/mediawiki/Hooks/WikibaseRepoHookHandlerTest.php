<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks;

use Wikibase\Lexeme\Hooks\WikibaseRepoHookHandler;
use Wikibase\Lexeme\MediaWiki\ParserOutput\LexemeParserOutputUpdater;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * @covers \Wikibase\Lexeme\Hooks\WikibaseRepoHookHandler
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class WikibaseRepoHookHandlerTest extends \MediaWikiIntegrationTestCase {
	public function testOnWikibaseRepoEntityTypes_enableRepoTrue() {
		$this->overrideConfigValue( 'LexemeEnableRepo', true );

		$handler = new WikibaseRepoHookHandler();
		$entityTypeDefinitions = [];
		$handler->onWikibaseRepoEntityTypes( $entityTypeDefinitions );

		$this->assertArrayHasKey( 'lexeme', $entityTypeDefinitions );
		$this->assertArrayHasKey( 'form', $entityTypeDefinitions );
		$this->assertArrayHasKey( 'sense', $entityTypeDefinitions );
	}

	public function testOnWikibaseRepoEntityTypes_enableRepoFalse() {
		$this->overrideConfigValue( 'LexemeEnableRepo', false );

		$handler = new WikibaseRepoHookHandler();
		$entityTypeDefinitions = [];
		$handler->onWikibaseRepoEntityTypes( $entityTypeDefinitions );

		$this->assertArrayEquals( [], $entityTypeDefinitions );
	}

	public function testOnWikibaseRepoOnParserOutputUpdaterConstruction() {
		$entityUpdaters = [];
		$statementDataUpdater = $this->createMock( StatementDataUpdater::class );

		$handler = new WikibaseRepoHookHandler();
		$handler->onWikibaseRepoOnParserOutputUpdaterConstruction( $statementDataUpdater, $entityUpdaters );
		$this->assertEquals(
			new LexemeParserOutputUpdater( $statementDataUpdater ),
			$entityUpdaters[ 0 ]
		);
	}
}

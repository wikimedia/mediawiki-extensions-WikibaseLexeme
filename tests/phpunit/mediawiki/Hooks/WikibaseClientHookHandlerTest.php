<?php

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Hooks\WikibaseClientHookHandler;

/**
 * @covers \Wikibase\Lexeme\Hooks\WikibaseClientHookHandler
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class WikibaseClientHookHandlerTest extends TestCase {
	public function testOnWikibaseClientDataTypes() {
		$datatypes = [];

		$handler = new WikibaseClientHookHandler();
		$handler->onWikibaseClientDataTypes( $datatypes );

		$this->assertArrayHasKey( 'PT:wikibase-lexeme', $datatypes );
		$this->assertArrayHasKey( 'PT:wikibase-form', $datatypes );
		$this->assertArrayHasKey( 'PT:wikibase-sense', $datatypes );
	}

	public function testOnWikibaseClientEntityTypes() {
		$entityTypeDefinitions = [];

		$handler = new WikibaseClientHookHandler();
		$handler->onWikibaseClientEntityTypes( $entityTypeDefinitions );

		$this->assertArrayHasKey( 'lexeme', $entityTypeDefinitions );
		$this->assertArrayHasKey( 'form', $entityTypeDefinitions );
		$this->assertArrayHasKey( 'sense', $entityTypeDefinitions );
	}
}

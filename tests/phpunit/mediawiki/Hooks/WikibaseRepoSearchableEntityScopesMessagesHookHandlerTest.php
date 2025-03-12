<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Hooks\WikibaseRepoSearchableEntityScopesMessagesHookHandler;

/**
 * @covers \Wikibase\Lexeme\Hooks\WikibaseRepoSearchableEntityScopesMessagesHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoSearchableEntityScopesMessagesHookHandlerTest extends TestCase {

	public function testHandleCorrectlyAddsMessage() {
		$handler = new WikibaseRepoSearchableEntityScopesMessagesHookHandler();

		$messages = [];
		$handler->onWikibaseRepoSearchableEntityScopesMessages( $messages );
		$this->assertEquals(
			WikibaseRepoSearchableEntityScopesMessagesHookHandler::LEXEME_MESSAGE_KEY,
			$messages[Lexeme::ENTITY_TYPE]
		);
	}

	public function testMessageOnlyAddedOnce() {
		$handler = new WikibaseRepoSearchableEntityScopesMessagesHookHandler();

		$messages = [
			Lexeme::ENTITY_TYPE => WikibaseRepoSearchableEntityScopesMessagesHookHandler::LEXEME_MESSAGE_KEY,
		];
		$handler->onWikibaseRepoSearchableEntityScopesMessages( $messages );
		$this->assertCount( 1, $messages );
	}

}

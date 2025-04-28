<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Hooks\WikibaseContentLanguagesHookHandler;
use Wikibase\Lib\ContentLanguages;

/**
 * @covers Wikibase\Lexeme\Hooks\WikibaseContentLanguagesHookHandler
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class WikibaseContentLanguagesHookHandlerTest extends TestCase {

	public function testOnWikibaseContentLanguages() {
		$contentLanguages = [];
		$lexemeTermLanguages = $this->createMock( ContentLanguages::class );

		$handler = new WikibaseContentLanguagesHookHandler( $lexemeTermLanguages );
		$handler->onWikibaseContentLanguages( $contentLanguages );

		$this->assertSame( $lexemeTermLanguages, $contentLanguages[ 'term-lexicographical' ] );
	}
}

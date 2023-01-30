<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\WikibaseLexemeHooks;
use Wikibase\Lexeme\WikibaseLexemeServices;

/**
 * @covers \Wikibase\Lexeme\WikibaseLexemeHooks
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeHooksTest extends TestCase {

	public function testOnCanonicalNamespaces_CalledFirstTime_RegistersLexemeNamespaces() {
		$namespaces = [];
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$namespaceId = $config->get( 'LexemeNamespace' );

		WikibaseLexemeHooks::onCanonicalNamespaces( $namespaces );

		$this->assertEquals( 'Lexeme', $namespaces[$namespaceId] );
		$this->assertEquals( 'Lexeme_talk', $namespaces[$namespaceId + 1] );
	}

	public function testOnCanonicalNamespaces_CalledMultipleTimes_RegistersLexemeNamespace() {
		$namespaces = [];
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$namespaceId = $config->get( 'LexemeNamespace' );

		WikibaseLexemeHooks::onCanonicalNamespaces( $namespaces );
		WikibaseLexemeHooks::onCanonicalNamespaces( $namespaces );

		$this->assertEquals( 'Lexeme', $namespaces[$namespaceId] );
	}

	public function testOnCanonicalNamespaces_NamespaceIdIsAlreadyRegistered_ThrowsAnException() {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$namespaceId = $config->get( 'LexemeNamespace' );
		$namespaces = [ $namespaceId => 'SomeOtherNamespace' ];

		$this->expectException( \Exception::class );
		WikibaseLexemeHooks::onCanonicalNamespaces( $namespaces );
	}

	public function testOnWikibaseContentLanguages() {
		$contentLanguages = [];
		$lexemeTermLanguages = WikibaseLexemeServices::getTermLanguages();

		WikibaseLexemeHooks::onWikibaseContentLanguages( $contentLanguages );

		$this->assertSame( $lexemeTermLanguages, $contentLanguages['term-lexicographical'] );
	}

}

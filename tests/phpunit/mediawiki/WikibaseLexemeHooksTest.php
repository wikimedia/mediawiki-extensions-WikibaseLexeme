<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWiki\MediaWikiServices;
use Wikibase\Lexeme\WikibaseLexemeHooks;

/**
 * @covers \Wikibase\Lexeme\WikibaseLexemeHooks
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class WikibaseLexemeHooksTest extends \PHPUnit_Framework_TestCase {

	public function testOnCanonicalNamespaces_ReturnsTrue() {
		$namespaces = [];

		$result = WikibaseLexemeHooks::onCanonicalNamespaces( $namespaces );

		$this->assertSuccessfulHookResult( $result );
	}

	public function testOnCanonicalNamespaces_CalledFirstTime_RegistersLexemeNamespace() {
		$namespaces = [];
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$namespaceId = $config->get( 'LexemeNamespace' );

		WikibaseLexemeHooks::onCanonicalNamespaces( $namespaces );

		$this->assertEquals( 'Lexeme', $namespaces[$namespaceId] );
	}

	public function testOnCanonicalNamespaces_CalledFirstTime_RegistersLexemeTalkNamespace() {
		$namespaces = [];
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$namespaceId = $config->get( 'LexemeTalkNamespace' );

		WikibaseLexemeHooks::onCanonicalNamespaces( $namespaces );

		$this->assertEquals( 'Lexeme_Talk', $namespaces[$namespaceId] );
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

		$this->setExpectedException( \Exception::class );
		WikibaseLexemeHooks::onCanonicalNamespaces( $namespaces );
	}

	private function assertSuccessfulHookResult( $result ) {
		$this->assertNotFalse( $result );
		$this->assertNotInternalType( 'string', $result );
	}

}

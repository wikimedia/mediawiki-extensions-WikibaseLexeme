<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class LexemeNamespaceTest extends TestCase {

	/**
	 * @dataProvider provideNamespacesAndMoveability
	 */
	public function testNamespaceMoveability( $namespace, $moveable ) {
		$this->assertSame( $moveable, MediaWikiServices::getInstance()->getNamespaceInfo()
			->isMovable( $namespace ) );
	}

	public function provideNamespacesAndMoveability() {
		global $wgLexemeNamespace;
		yield 'Lexeme' => [ $wgLexemeNamespace, false ];
		yield 'Lexeme talk' => [ $wgLexemeNamespace + 1, true ];
	}

}

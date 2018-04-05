<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MWNamespace;
use PHPUnit\Framework\TestCase;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class LexemeMWNamespaceTest extends TestCase {

	/**
	 * @dataProvider provideNamespacesAndMoveability
	 */
	public function testNamespaceMoveability( $namespace, $moveable ) {
		$this->assertSame( $moveable, MWNamespace::isMovable( $namespace ) );
	}

	public function provideNamespacesAndMoveability() {
		global $wgLexemeNamespace, $wgLexemeTalkNamespace;
		yield $wgLexemeNamespace => [ $wgLexemeNamespace, false ];
		yield $wgLexemeTalkNamespace => [ $wgLexemeTalkNamespace, true ];
	}

}

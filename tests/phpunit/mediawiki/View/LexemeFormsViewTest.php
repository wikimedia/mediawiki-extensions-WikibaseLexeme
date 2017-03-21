<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Wikibase\Lexeme\View\LexemeFormsView;

/**
 * @covers Wikibase\Lexeme\View\LexemeFormsView
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormsViewTest extends PHPUnit_Framework_TestCase {

	public function testHtmlContainsTheFormsHeadline() {
		$view = new LexemeFormsView();
		$html = $view->getHtml();

		$this->assertSame( 1, substr_count( $html, '</h2>' ) );
		$this->assertContains( ' id="forms"', $html );
	}

}

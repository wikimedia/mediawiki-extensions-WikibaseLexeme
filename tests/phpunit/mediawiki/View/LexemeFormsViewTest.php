<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Wikibase\Lexeme\View\LexemeFormsView;
use Wikibase\View\DummyLocalizedTextProvider;

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
		$view = $this->newFormsView();
		$html = $view->getHtml( [] );

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'h2' ) )
					->andAlso( havingChild(
						both( withAttribute( 'id' )->havingValue( 'forms' ) )
							->andAlso( havingTextContents( '(wikibase-lexeme-view-forms)' ) )
					) )
			) ) )
		);
	}

	public function testHtmlContainsFormsContainer() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline(
				'<div class="wikibase-lexeme-forms">'
			) ) ) )
		);
	}

	private function newFormsView() {
		return new LexemeFormsView( new DummyLocalizedTextProvider() );
	}

}

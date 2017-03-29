<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Wikibase\Lexeme\View\SensesView;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers Wikibase\Lexeme\View\LexemeSensesView
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class SensesViewTest extends PHPUnit_Framework_TestCase {

	public function testHtmlContainsTheSensesHeadline() {
		$view = $this->newSensesView();
		$html = $view->getHtml( [] );

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'h2' ) )
					->andAlso( havingChild(
						both( withAttribute( 'id' )->havingValue( 'senses' ) )
							->andAlso( havingTextContents( '(wikibase-lexeme-view-senses)' ) )
					) )
			) ) )
		);
	}

	public function testHtmlContainsSensesContainer() {
		$view = $this->newSensesView();
		$html = $view->getHtml( [] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline(
				'<div class="wikibase-lexeme-senses">'
			) ) ) )
		);
	}

	private function newSensesView() {
		return new SensesView( new DummyLocalizedTextProvider() );
	}

}

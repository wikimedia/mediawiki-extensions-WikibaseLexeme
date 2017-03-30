<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Wikibase\Lexeme\DataModel\LexemeForm;
use Wikibase\Lexeme\DataModel\LexemeFormId;
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

	public function testHtmlContainsFormRepresentationWithIdÁndLanguage() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [
				new LexemeForm( new LexemeFormId( 'FORM_ID' ), 'FORM_REPRESENTATION' )
			] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( both( tagMatchingOutline(
				'<h3 class="wikibase-lexeme-form-representation" lang="some language">'
				) )->andAlso( havingTextContents( 'FORM_REPRESENTATION (FORM_ID)' ) ) ) ) )
			);
	}

	private function newFormsView() {
		return new LexemeFormsView( new DummyLocalizedTextProvider() );
	}

}

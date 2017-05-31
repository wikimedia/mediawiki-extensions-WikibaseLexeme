<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\View\SensesView;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
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

	public function testHtmlContainsGlossWithId() {
		$view = $this->newSensesView();
		$html = $view->getHtml( [
			new Sense(
				new SenseId( 'S1' ),
				new TermList( [ new Term( 'en', 'test gloss' ) ] ),
				new StatementList()
			)
		] );

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( tagMatchingOutline( '<h3 dir="ltr" lang="en">' ) )
					->andAlso( havingTextContents( containsString( 'test gloss (S1)' ) )
					)
				) ) )
		);
	}

	public function testGivenNoGlossInDisplayLanguageHtmlContainsNoGlossMessage() {
		$view = $this->newSensesView();
		$html = $view->getHtml( [
			new Sense(
				new SenseId( 'S1' ),
				new TermList( [ new Term( 'de', 'Testgloss' ) ] ),
				new StatementList()
			)
		] );

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( tagMatchingOutline( '<h3 lang="qqx">' ) )
					->andAlso( havingTextContents( containsString( '(wikibase-lexeme-gloss-empty)' ) )
					)
			) ) )
		);
	}

	private function newSensesView() {
		return new SensesView(
			new DummyLocalizedTextProvider(),
			new MediaWikiLanguageDirectionalityLookup(),
			new LexemeTemplateFactory( [
				'wikibase-lexeme-sense' => '<h3 dir="$1" lang="$2"><span class="$3">$4</span> $5</h3>',
			] ),
			'en'
		);
	}

}

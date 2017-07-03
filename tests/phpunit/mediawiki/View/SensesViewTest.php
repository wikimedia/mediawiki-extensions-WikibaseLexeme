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
use Wikibase\View\StatementSectionsView;

/**
 * @covers Wikibase\Lexeme\View\SensesView
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class SensesViewTest extends PHPUnit_Framework_TestCase {

	const STATEMENT_SECTION_HTML = '<div class="statement-section"/>';

	public function testHtmlContainsTheSensesHeadline() {
		$this->markTestSkipped( 'Skipped until we remove VUE template from HTML' );
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
		$this->markTestSkipped( 'Skipped until we remove VUE template from HTML' );
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
		$this->markTestSkipped( 'Skipped until we remove VUE template from HTML' );
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
				both( tagMatchingOutline( '<span dir="ltr" lang="en">' ) )
					->andAlso( havingTextContents(
						both( containsString( 'test gloss' ) )
							->andAlso( containsString( 'S1' ) ) ) )
				) ) )
		);
	}

	public function testHtmlContainsStatementSection() {
		$this->markTestSkipped( 'Skipped until we remove VUE template from HTML' );
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
			is( htmlPiece( havingChild( tagMatchingOutline( self::STATEMENT_SECTION_HTML ) ) ) )
		);
	}

	private function newSensesView() {
		$statementSectionView = $this->getMockBuilder( StatementSectionsView::class )
			->disableOriginalConstructor()
			->getMock();
		$statementSectionView->method( 'getHtml' )
			->will( $this->returnValue( self::STATEMENT_SECTION_HTML ) );

		return new SensesView(
			new DummyLocalizedTextProvider(),
			new MediaWikiLanguageDirectionalityLookup(),
			new LexemeTemplateFactory( [
				'wikibase-lexeme-sense' => '<div class="wikibase-lexeme-sense" data-sense-id="$1">
    $2
    $3
</div>',
			] ),
			$statementSectionView,
			'en'
		);
	}

}

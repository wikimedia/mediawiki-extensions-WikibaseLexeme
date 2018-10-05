<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Prophecy\Argument;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DataModel\SenseSet;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lexeme\View\SensesView;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementGroupListView;

/**
 * @covers \Wikibase\Lexeme\View\SensesView
 *
 * @license GPL-2.0-or-later
 */
class SensesViewTest extends TestCase {

	use HamcrestPHPUnitIntegration;
	use PHPUnit4And6Compat;

	const STATEMENT_SECTION_HTML = '<div class="statement-section"/>';

	public function testHtmlContainsTheSensesHeadline() {
		$view = $this->newSensesView();
		$html = $view->getHtml( new SenseSet( [] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'h2' ) )
					->andAlso( havingChild(
						both( withAttribute( 'id' )->havingValue( 'senses' ) )
							->andAlso( havingTextContents( '(wikibaselexeme-header-senses)' ) )
					) )
			) ) )
		);
	}

	public function testHtmlContainsSensesContainer() {
		$view = $this->newSensesView();
		$html = $view->getHtml( new SenseSet( [] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline(
				'<div class="wikibase-lexeme-senses">'
			) ) ) )
		);
	}

	public function testHtmlContainsGlossWithId() {
		$view = $this->newSensesView();
		$html = $view->getHtml( new SenseSet( [
			new Sense(
				new SenseId( 'L1-S1' ),
				new TermList( [ new Term( 'en', 'test gloss' ) ] ),
				new StatementList()
			)
		] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece(
				havingChild( allOf(
					tagMatchingOutline( '<div class="wikibase-lexeme-sense">' ),
					havingChild(
						both( tagMatchingOutline( '<span dir="ltr" lang="en">' ) )
							->andAlso( havingTextContents(
									equalToIgnoringWhiteSpace( 'test gloss' ) )
							)
					),
					havingChild(
						both( tagMatchingOutline( '<div class="wikibase-lexeme-sense-id">' ) )
							->andAlso( havingTextContents( 'L1-S1' ) )
					)
				) )
			) )
		);
	}

	public function testHtmlContainsStatementSection() {
		$view = $this->newSensesView();
		$html = $view->getHtml( new SenseSet( [
			new Sense(
				new SenseId( 'L1-S1' ),
				new TermList( [ new Term( 'en', 'test gloss' ) ] ),
				new StatementList()
			)
		] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline( self::STATEMENT_SECTION_HTML ) ) ) )
		);
	}

	public function testStatementGroupsHaveSenseIdPrefix() {
		$statementGroupListView = $this->createMock( StatementGroupListView::class );
		$senseView = new SensesView(
			new DummyLocalizedTextProvider(),
			new MediaWikiLanguageDirectionalityLookup(),
			$this->newTemplateFactory(),
			$statementGroupListView,
			'en'
		);

		$senseId = 'L2-S3';
		$sense = NewSense::havingId( new SenseId( $senseId ) )
			->andLexeme( 'L2' )
			->build();
		$statementGroupListView->expects( $this->once() )
			->method( 'getHtml' )
			->with( [], 'S3' );

		$senseView->getHtml( new SenseSet( [ $sense ] ) );
	}

	private function newSensesView() : SensesView {
		$statementSectionView = $this->prophesize( StatementGroupListView::class );
		$statementSectionView->getHtml( Argument::any(), Argument::any() )
			->willReturn( self::STATEMENT_SECTION_HTML );

		return new SensesView(
			new DummyLocalizedTextProvider(),
			new MediaWikiLanguageDirectionalityLookup(),
			$this->newTemplateFactory(),
			$statementSectionView->reveal(),
			'en'
		);
	}

	private function newTemplateFactory() : LexemeTemplateFactory {
		return new LexemeTemplateFactory( [
			'wikibase-lexeme-sense' => '
					<div class="wikibase-lexeme-sense">
						<div class="wikibase-lexeme-sense-header">
							<div class="wikibase-lexeme-sense-id">$1</div>
							<div class="sense-representations">$2</div>
						</div>
						$3
						$4
					</div>'
		] );
	}

}

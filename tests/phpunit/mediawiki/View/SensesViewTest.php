<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\Presentation\View\SensesView;
use Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementGroupListView;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\SensesView
 *
 * @license GPL-2.0-or-later
 */
class SensesViewTest extends TestCase {

	use HamcrestPHPUnitIntegration;

	private const STATEMENT_SECTION_HTML = '<div class="statement-section"/>';

	public function testHtmlContainsTheSensesHeadline() {
		$view = $this->newSensesView();
		$html = $view->getHtml( new SenseSet( [] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'h2' ) )
					->andAlso( withAttribute( 'id' )->havingValue( 'senses' ) )
					->andAlso( havingTextContents( '(wikibaselexeme-header-senses)' ) )
					) )
			)
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
						both( tagMatchingOutline( '<td class="wikibase-lexeme-sense-gloss-value-cell"
							dir="ltr" lang="en">' ) )
						->andAlso( havingChild(
								both( tagMatchingOutline( '<span>' ) )
									->andAlso( havingTextContents(
										equalToIgnoringWhiteSpace( 'test gloss' )
									) )
						) )
					),
					havingChild(
						both( tagMatchingOutline( '<span>' ) )
						->andAlso( havingTextContents( 'English' ) )
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
			WikibaseRepo::getLanguageDirectionalityLookup(),
			$this->newTemplateFactory(),
			$statementGroupListView,
			$this->getLanguageNameMock()
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

	public function testGlossesOrder() {
		$sensesView = $this->newSensesView();

		$senseId = 'L2-S3';
		$sense = NewSense::havingId( new SenseId( $senseId ) )
			->andLexeme( 'L2' )
			->withGloss( 'en', 'Foo' )
			->withGloss( 'fa', 'Bar' )
			->build();

		$html = $sensesView->getHtml( new SenseSet( [ $sense ] ) );
		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild(
					both( tagMatchingOutline( '<td class="wikibase-lexeme-sense-gloss-value-cell"
							dir="ltr" lang="en">' ) )
						->andAlso( havingChild(
							both( tagMatchingOutline( '<span class="wikibase-lexeme-sense-gloss-value">'
							) )
								->andAlso( havingTextContents( containsString( 'Foo' ) ) )
						) )
			) ) )
		);

		$this->assertStringContainsString(
			'<td class="wikibase-lexeme-sense-gloss-value-cell" dir="rtl" lang="fa">',
			explode( '<tr class="wikibase-lexeme-sense-gloss">', $html, 3 )[2]
		);
	}

	private function newSensesView(): SensesView {
		$statementGroupListView = $this->createMock( StatementGroupListView::class );
		$statementGroupListView->method( 'getHtml' )
			->willReturn( self::STATEMENT_SECTION_HTML );

		return new SensesView(
			new DummyLocalizedTextProvider(),
			WikibaseRepo::getLanguageDirectionalityLookup(),
			$this->newTemplateFactory(),
			$statementGroupListView,
			$this->getLanguageNameMock()
		);
	}

	private function newTemplateFactory(): LexemeTemplateFactory {
		return LexemeTemplateFactory::factory();
	}

	private function getLanguageNameMock(): LexemeLanguageNameLookup {
		$languageNameMock = $this->createMock(
			LexemeLanguageNameLookup::class
		);
		$languageNameMock->method( 'getName' )->willReturn( 'English' );
		return $languageNameMock;
	}

}

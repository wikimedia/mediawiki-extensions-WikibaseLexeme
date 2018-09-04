<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Prophecy\Argument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\View\FormsView;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementGroupListView;

/**
 * @covers \Wikibase\Lexeme\View\FormsView
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormsViewTest extends TestCase {

	use HamcrestPHPUnitIntegration;
	use PHPUnit4And6Compat;

	const STATEMENT_LIST_HTML = '<div class="statement-list"></div>';

	public function testHtmlContainsTheFormsHeadline() {
		$view = $this->newFormsView();
		$html = $view->getHtml( new FormSet() );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'h2' ) )
					->andAlso( havingChild(
						both( withAttribute( 'id' )->havingValue( 'forms' ) )
							->andAlso( havingTextContents( '(wikibaselexeme-header-forms)' ) )
					) )
			) ) )
		);
	}

	public function testHtmlContainsFormsContainer() {
		$view = $this->newFormsView();
		$html = $view->getHtml( new FormSet() );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline(
				'<div class="wikibase-lexeme-forms">'
			) ) ) )
		);
	}

	public function testHtmlContainsFormRepresentationWithIdAndLanguage() {
		$view = $this->newFormsView();
		$html = $view->getHtml( new FormSet( [
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'FORM_REPRESENTATION' )
				->build()
		] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece(
				both( havingChild(
					allOf(
						tagMatchingOutline(
							'<span class="representation-widget_representation-value" lang="en"/>'
						),
						havingTextContents( containsString( 'FORM_REPRESENTATION' ) )
					) ) )
				->andAlso( havingChild(
					allOf(
						withClass( 'representation-widget_representation-language' ),
						havingTextContents( containsString( 'en' ) )
					)
				) ) ) )
		);
	}

	public function testHtmlContainsFormId() {
		$view = $this->newFormsView();
		$html = $view->getHtml( new FormSet( [
			NewForm::havingId( 'F1' )->build()
		] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece(
				havingChild(
					havingTextContents( containsString( 'F1' ) )
			) ) )
		);
	}

	public function testHtmlContainsFormGrammaticalFeatures() {
		$view = $this->newFormsView();

		$html = $view->getHtml( new FormSet( [
			NewForm::havingGrammaticalFeature( 'Q1' )->build()
		] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild( havingTextContents( containsString( 'Q1' ) ) ) ) )
		);
	}

	public function testHtmlContainsStatementSection() {
		$view = $this->newFormsView();
		$html = $view->getHtml( new FormSet( [
			NewForm::any()->build()
		] ) );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline( self::STATEMENT_LIST_HTML ) ) ) )
		);
	}

	private function newFormsView() {
		$statementSectionView = $this->prophesize( StatementGroupListView::class );
		$statementSectionView->getHtml( Argument::any() )->willReturn( self::STATEMENT_LIST_HTML );

		$idFormatter = $this->createMock( EntityIdFormatter::class );
		$idFormatter->method( 'formatEntityId' )
			->willReturnCallback( function ( EntityId $entityId ) {
				return $entityId->serialize();
			} );

		return new FormsView(
			new DummyLocalizedTextProvider(),
			new LexemeTemplateFactory( [
				'wikibase-lexeme-form' => '
					<div class="wikibase-lexeme-form">
						<div class="wikibase-lexeme-form-header">
							<div class="wikibase-lexeme-form-id">$1</div>
							<div class="form-representations">$2</div>
						</div>
						$3
						$4
					</div>',
				'wikibase-lexeme-form-grammatical-features' => '<div><div>$1</div><div>$2</div></div>'
			] ),
			$idFormatter,
			$statementSectionView->reveal()
		);
	}

}

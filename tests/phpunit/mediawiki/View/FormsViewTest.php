<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Presentation\View\FormsView;
use Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\Store\ItemOrderProvider;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementGroupListView;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\FormsView
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormsViewTest extends TestCase {

	use HamcrestPHPUnitIntegration;

	private const STATEMENT_LIST_HTML = '<div class="statement-list"></div>';

	public function testHtmlContainsTheFormsHeadline() {
		$view = $this->newFormsView();
		$html = $view->getHtml( new FormSet() );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'h2' ) )
					->andAlso( withAttribute( 'id' )->havingValue( 'forms' ) )
					->andAlso( havingTextContents( '(wikibaselexeme-header-forms)' ) )
					) )
			)
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

	public function testStatementGroupsHaveFormIdPrefix() {
		$statementGroupListView = $this->createMock( StatementGroupListView::class );
		$formsView = new FormsView(
			new DummyLocalizedTextProvider(),
			$this->newTemplateFactory(),
			$this->createMock( EntityIdFormatter::class ),
			$statementGroupListView,
			WikibaseLexemeServices::getGrammaticalFeaturesOrderProvider()
		);

		$formId = 'L2-F3';
		$form = NewForm::havingId( new FormId( $formId ) )
			->andLexeme( 'L2' )
			->build();
		$statementGroupListView->expects( $this->once() )
			->method( 'getHtml' )
			->with( [], 'F3' );

		$formsView->getHtml( new FormSet( [ $form ] ) );
	}

	public function testGrammaticalFeaturesOrder() {
		$statementSectionView = $this->createMock( StatementGroupListView::class );
		$statementSectionView->method( 'getHtml' )
			->willReturn( self::STATEMENT_LIST_HTML );

		$idFormatter = $this->createMock( EntityIdFormatter::class );
		$idFormatter->method( 'formatEntityId' )
			->willReturnCallback( static function ( EntityId $entityId ) {
				return $entityId->serialize();
			} );

		$grammaticalFeaturesOrderProvider = $this->createMock( ItemOrderProvider::class );
		$grammaticalFeaturesOrderProvider->method( 'getItemOrder' )
			->willReturn( [ 'Q5' => 0, 'Q4' => 1, 'Q8' => 2 ]
		);

		$formsView = new FormsView(
			new DummyLocalizedTextProvider(),
			$this->newTemplateFactory(),
			$idFormatter,
			$statementSectionView,
			$grammaticalFeaturesOrderProvider
		);

		$form = NewForm::havingId( new FormId( 'L2-F4' ) )
		->andGrammaticalFeature( 'Q4' )
		->andGrammaticalFeature( 'Q8' )
		->andGrammaticalFeature( 'Q5' )
			->build();

		$html = $formsView->getHtml( new FormSet( [ $form ] ) );

		$this->assertThatHamcrest(
			$html,
			stringContainsInOrder( 'Q5', 'Q4', 'Q8' )
		);
	}

	private function newFormsView(): FormsView {
		$statementSectionView = $this->createMock( StatementGroupListView::class );
		$statementSectionView->method( 'getHtml' )
			->willReturn( self::STATEMENT_LIST_HTML );

		$idFormatter = $this->createMock( EntityIdFormatter::class );
		$idFormatter->method( 'formatEntityId' )
			->willReturnCallback( static function ( EntityId $entityId ) {
				return $entityId->serialize();
			} );

		return new FormsView(
			new DummyLocalizedTextProvider(),
			$this->newTemplateFactory(),
			$idFormatter,
			$statementSectionView,
			WikibaseLexemeServices::getGrammaticalFeaturesOrderProvider()
		);
	}

	private function newTemplateFactory(): LexemeTemplateFactory {
		return LexemeTemplateFactory::factory();
	}

}

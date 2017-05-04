<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Prophecy\Argument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lexeme\DataModel\LexemeForm;
use Wikibase\Lexeme\DataModel\LexemeFormId;
use Wikibase\Lexeme\View\LexemeFormsView;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementSectionsView;

/**
 * @covers Wikibase\Lexeme\View\LexemeFormsView
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormsViewTest extends PHPUnit_Framework_TestCase {

	const STATEMENT_SECTION_HTML = '<div class="statement-section"></div>';

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

	public function testHtmlContainsFormRepresentationWithIdAndLanguage() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [
			new LexemeForm( new LexemeFormId( 'FORM_ID' ), 'FORM_REPRESENTATION', [] )
		] );

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( tagMatchingOutline( '<h3 lang="some language">' ) )
					->andAlso( havingTextContents( containsString( 'FORM_REPRESENTATION (FORM_ID)' ) ) )
			) ) )
		);
	}

	public function testHtmlContainsFormGrammaticalFeatures() {
		$view = $this->newFormsView();
		$grammaticalFeature = new ItemId( 'Q1' );
		$lexemeForm = new LexemeForm(
			new LexemeFormId( 'FORM_ID' ),
			'FORM_REPRESENTATION',
			[ $grammaticalFeature ]
		);

		$html = $view->getHtml( [ $lexemeForm ] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( havingTextContents( containsString( 'Q1' ) ) ) ) )
		);
	}

	public function testHtmlContainsStatementSection() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [
			new LexemeForm( new LexemeFormId( 'FORM_ID' ), 'FORM_REPRESENTATION', [] )
		] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline( self::STATEMENT_SECTION_HTML ) ) ) )
		);
	}

	private function newFormsView() {
		$statementSectionView = $this->prophesize( StatementSectionsView::class );
		$statementSectionView->getHtml( Argument::any() )->willReturn( self::STATEMENT_SECTION_HTML );

		return new LexemeFormsView(
			new DummyLocalizedTextProvider(),
			new LexemeTemplateFactory( [
				'wikibase-lexeme-form' => '<h3 lang="$1">$2 $3</h3>$4 $5',
				'wikibase-lexeme-form-grammatical-features' => '<div>$1</div>'
			] ),
			new EntityIdHtmlLinkFormatter(
				$this->getMock( LabelDescriptionLookup::class ),
				$this->getMock( EntityTitleLookup::class ),
				$this->getMock( LanguageNameLookup::class )
			),
			$statementSectionView->reveal()
		);
	}

}

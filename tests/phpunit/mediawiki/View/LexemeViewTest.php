<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\View\FormsView;
use Wikibase\Lexeme\View\SensesView;
use Wikibase\Lexeme\View\LexemeView;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer;
use Wikibase\View\EntityTermsView;
use Wikibase\View\EntityView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\View\LexemeView
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Kreuz
 */
class LexemeViewTest extends \MediaWikiTestCase {

	/**
	 * @return FormsView
	 */
	private function newFormsViewMock() {
		$view = $this->getMockBuilder( FormsView::class )
			->disableOriginalConstructor()
			->getMock();

		$view->method( 'getHtml' )
			->will( $this->returnValue( "FormsView::getHtml\n" ) );

		return $view;
	}

	/**
	 * @return SensesView
	 */
	private function newSensesViewMock() {
		$view = $this->getMockBuilder( SensesView::class )
			->disableOriginalConstructor()
			->getMock();

		$view->method( 'getHtml' )
			->will( $this->returnValue( "SensesView::getHtml\n" ) );

		return $view;
	}

	/**
	 * @param StatementList|null $expectedStatements
	 *
	 * @return StatementSectionsView
	 */
	private function newStatementSectionsViewMock( StatementList $expectedStatements = null ) {
		$statementSectionsView = $this->getMockBuilder( StatementSectionsView::class )
			->disableOriginalConstructor()
			->getMock();

		$statementSectionsView->expects( $expectedStatements ? $this->once() : $this->never() )
			->method( 'getHtml' )
			->with( $expectedStatements )
			->will( $this->returnValue( "StatementSectionsView::getHtml\n" ) );

		return $statementSectionsView;
	}

	/**
	 * @return LabelDescriptionLookup
	 */
	private function newLabelDescriptionLookup() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );

		$labelDescriptionLookup
			->method( 'getLabel' )
			->will(
				$this->returnCallback( function( ItemId $itemId ) {
					if ( $itemId->getSerialization() === 'Q1' ) {
						return null;
					}
					return new Term( 'en', '<ITEM-' . $itemId->getSerialization() . '>' );
				} )
			);

		return $labelDescriptionLookup;
	}

	/**
	 * @return EntityTermsView
	 */
	private function newEntityTermsViewMock() {
		$entityTermsView = $this->getMock( EntityTermsView::class );

		$entityTermsView->expects( $this->never() )
			->method( 'getHtml' );

		$entityTermsView->expects( $this->never() )
			->method( 'getTitleHtml' );

		return $entityTermsView;
	}

	/**
	 * @return LanguageDirectionalityLookup
	 */
	private function newLanguageDirectionalityLookupMock() {
		$languageDirectionalityLookup = $this->getMock( LanguageDirectionalityLookup::class );
		$languageDirectionalityLookup->method( 'getDirectionality' )
			->willReturn( 'auto' );

		return $languageDirectionalityLookup;
	}

	private function newLexemeView( StatementList $expectedStatements = null ) {
		$languageDirectionalityLookup = $this->newLanguageDirectionalityLookupMock();

		$htmlTermRenderer = new FallbackHintHtmlTermRenderer(
			$languageDirectionalityLookup,
			new LanguageNameLookup( 'en' )
		);

		$linkFormatter = $this->getMockBuilder( EntityIdHtmlLinkFormatter::class )
			->disableOriginalConstructor()
			->getMock();
		$linkFormatter->method( 'formatEntityId' )
			->willReturnCallback( function( EntityId $entityId ) {
				$id = $entityId->getSerialization();
				$label = 'LABEL OF ' . $id;
				return "<a href='foobar/$id'>$label</a>";
			} );

		return new LexemeView(
			TemplateFactory::getDefaultInstance(),
			$this->newEntityTermsViewMock(),
			$languageDirectionalityLookup,
			'en',
			$this->newFormsViewMock(),
			$this->newSensesViewMock(),
			$this->newStatementSectionsViewMock( $expectedStatements ),
			$htmlTermRenderer,
			$this->newLabelDescriptionLookup(),
			$linkFormatter
		);
	}

	public function testInstantiate() {
		$view = $this->newLexemeView();
		$this->assertInstanceOf( LexemeView::class, $view );
		$this->assertInstanceOf( EntityView::class, $view );
	}

	public function testGetHtml_invalidEntityType() {
		$view = $this->newLexemeView();

		/** @var EntityDocument $entity */
		$entity = $this->getMock( EntityDocument::class );

		$this->setExpectedException( InvalidArgumentException::class );
		$view->getHtml( $entity );
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtml( Lexeme $lexeme ) {
		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getHtml( $lexeme );
		$this->assertInternalType( 'string', $html );
		$this->assertContains( 'id="wb-lexeme-' . ( $lexeme->getId() ?: 'new' ) . '"', $html );
		$this->assertContains( 'class="wikibase-entityview wb-lexeme"', $html );
		$this->assertContains( 'FormsView::getHtml', $html );
		$this->assertContains( 'StatementSectionsView::getHtml', $html );
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtmlSensesIncluded( Lexeme $lexeme ) {
		$this->setMwGlobals( 'wgLexemeDisableSenses', false );
		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getHtml( $lexeme );
		$this->assertInternalType( 'string', $html );
		$this->assertContains( 'id="wb-lexeme-' . ( $lexeme->getId() ?: 'new' ) . '"', $html );
		$this->assertContains( 'class="wikibase-entityview wb-lexeme"', $html );
		$this->assertContains( 'FormsView::getHtml', $html );
		$this->assertContains( 'SensesView::getHtml', $html );
		$this->assertContains( 'StatementSectionsView::getHtml', $html );
	}

	public function provideTestGetHtml() {
		$lexemeId = new LexemeId( 'L1' );
		$lexicalCategory = new ItemId( 'Q32' );
		$language = new ItemId( 'Q11' );
		$statements = new StatementList( [
			new Statement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) )
		] );

		return [
			[
				new Lexeme( $lexemeId, null, $lexicalCategory, $language ),
			],
			[
				new Lexeme( $lexemeId, null, $lexicalCategory, $language, $statements ),
			],
		];
	}

	public function testGetTitleHtml_invalidEntityType() {
		$view = $this->newLexemeView();

		/** @var EntityDocument $entity */
		$entity = $this->getMock( EntityDocument::class );
		$this->setExpectedException( ParameterTypeException::class );
		$view->getTitleHtml( $entity );
	}

	public function testGetHtmlForLanguage() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );

		$lexeme = new Lexeme( $lexemeId, null, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getHtml( $lexeme );
		$this->assertInternalType( 'string', $html );
		assertThat(
			$html,
			is(
				htmlPiece(
					havingChild(
						both( withClass( 'language-lexical-category-widget_language' ) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<a href="foobar/Q2"/>' )
								)->andAlso(
									havingTextContents( containsString( 'LABEL OF Q2' ) )
								)
							) )
					)
				)
			)
		);
		$this->assertContains(
			'<div id="toc"></div>'
			. "StatementSectionsView::getHtml\n"
			. "FormsView::getHtml\n"
			. '</div>',
			$html
		);
	}

	public function testGetHtmlForLexicalCategory() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );

		$lexeme = new Lexeme( $lexemeId, null, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getHtml( $lexeme );
		$this->assertInternalType( 'string', $html );
		assertThat(
			$html,
			is(
				htmlPiece(
					havingChild(
						both( withClass( 'language-lexical-category-widget_lexical-category' ) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<a href="foobar/Q3"/>' )
								)->andAlso(
									havingTextContents( containsString( 'LABEL OF Q3' ) )
								)
							) )
					)
				)
			)
		);
		$this->assertContains(
			'<div id="toc"></div>'
			. "StatementSectionsView::getHtml\n"
			. "FormsView::getHtml\n"
			. '</div>',
			$html
		);
	}

	public function testGetHtmlForLanguageSensesIncluded() {
		$this->setMwGlobals( 'wgLexemeDisableSenses', false );
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );

		$lexeme = new Lexeme( $lexemeId, null, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getHtml( $lexeme );
		$this->assertInternalType( 'string', $html );
		assertThat(
			$html,
			is(
				htmlPiece(
					havingChild(
						both( withClass( 'language-lexical-category-widget_language' ) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<a href="foobar/Q2"/>' )
								)->andAlso(
									havingTextContents( containsString( 'LABEL OF Q2' ) )
								)
							) )
					)
				)
			)
		);
		$this->assertContains(
			'<div id="toc"></div>'
			. "StatementSectionsView::getHtml\n"
			. "FormsView::getHtml\n"
			. "SensesView::getHtml\n"
			. '</div>',
			$html
		);
	}

	public function testGetHtmlForLexicalCategorySensesIncluded() {
		$this->setMwGlobals( 'wgLexemeDisableSenses', false );
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );

		$lexeme = new Lexeme( $lexemeId, null, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getHtml( $lexeme );
		$this->assertInternalType( 'string', $html );
		assertThat(
			$html,
			is(
				htmlPiece(
					havingChild(
						both( withClass( 'language-lexical-category-widget_lexical-category' ) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<a href="foobar/Q3"/>' )
								)->andAlso(
									havingTextContents( containsString( 'LABEL OF Q3' ) )
								)
							) )
					)
				)
			)
		);
		$this->assertContains(
			'<div id="toc"></div>'
			. "StatementSectionsView::getHtml\n"
			. "FormsView::getHtml\n"
			. "SensesView::getHtml\n"
			. '</div>',
			$html
		);
	}

}

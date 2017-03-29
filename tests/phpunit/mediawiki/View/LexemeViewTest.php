<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\View\LexemeFormsView;
use Wikibase\Lexeme\View\SensesView;
use Wikibase\Lexeme\View\LexemeView;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\ParserOutput\FallbackHintHtmlTermRenderer;
use Wikibase\View\EntityTermsView;
use Wikibase\View\EntityView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers Wikibase\Lexeme\View\LexemeView
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Mättig
 */
class LexemeViewTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return LexemeFormsView
	 */
	private function newFormsViewMock() {
		$view = $this->getMockBuilder( LexemeFormsView::class )
			->disableOriginalConstructor()
			->getMock();

		$view->method( 'getHtml' )
			->will( $this->returnValue( "lexemeFormsView->getHtml\n" ) );

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
			->will( $this->returnValue( "lexemeSensesView->getHtml\n" ) );

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
			->will( $this->returnValue( "statementSectionsView->getHtml\n" ) );

		return $statementSectionsView;
	}

	/**
	 * @return LabelDescriptionLookup
	 */
	private function newLabelDescriptionLookup() {
		$labelDescriptionLookup = $this->getMockBuilder( LabelDescriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();
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
		$entityTermsView = $this->getMockBuilder( EntityTermsView::class )
			->disableOriginalConstructor()
			->getMock();

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

		return new LexemeView(
			TemplateFactory::getDefaultInstance(),
			$this->newEntityTermsViewMock(),
			$languageDirectionalityLookup,
			'en',
			$this->newFormsViewMock(),
			$this->newSensesViewMock(),
			$this->newStatementSectionsViewMock( $expectedStatements ),
			$htmlTermRenderer,
			$this->newLabelDescriptionLookup()
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
		$this->assertContains( 'lexemeFormsView->getHtml', $html );
		$this->assertContains( 'lexemeSensesView->getHtml', $html );
		$this->assertContains( 'statementSectionsView->getHtml', $html );
	}

	public function provideTestGetHtml() {
		$lexemeId = new LexemeId( 'L1' );
		$statements = new StatementList( [
			new Statement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) )
		] );

		return [
			[
				new Lexeme(),
			],
			[
				new Lexeme( $lexemeId ),
			],
			[
				new Lexeme( $lexemeId, null, null, null, $statements ),
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

	public function provideTestGetHtmlForLexicalCategoryAndLanguage() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );
		$missingLabelItem = new ItemId( 'Q1' );

		return [
			[
				new Lexeme(),
				''
			],
			[
				new Lexeme( $lexemeId, null, $lexicalCategory ),
				'&lt;ITEM-Q3&gt;'
			],
			[
				new Lexeme( $lexemeId, null, null, $language ),
				'Lexeme in &lt;ITEM-Q2&gt;'
			],
			[
				new Lexeme( $lexemeId, null, $lexicalCategory, $language ),
				'&lt;ITEM-Q3&gt; in &lt;ITEM-Q2&gt;'
			],
			[
				new Lexeme( $lexemeId, null, $missingLabelItem, $language ),
				'Q1 in &lt;ITEM-Q2&gt;'
			],
		];
	}

	/**
	 * @dataProvider provideTestGetHtmlForLexicalCategoryAndLanguage
	 */
	public function testGetHtmlForLexicalCategoryAndLanguage(
		Lexeme $entity,
		$expectedHeadline
	) {
		$view = $this->newLexemeView( $entity->getStatements() );

		$html = $view->getHtml( $entity );
		$this->assertInternalType( 'string', $html );
		$this->assertContains(
			'<div class="wikibase-entityview-main">'
			. $expectedHeadline
			. '<div id="toc"></div>'
			. "statementSectionsView->getHtml\n"
			. "lexemeFormsView->getHtml\n"
			. "lexemeSensesView->getHtml\n"
			. '</div>',
			$html
		);
	}

}

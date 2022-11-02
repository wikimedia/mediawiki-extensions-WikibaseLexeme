<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use HamcrestPHPUnitIntegration;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Presentation\View\FormsView;
use Wikibase\Lexeme\Presentation\View\LexemeView;
use Wikibase\Lexeme\Presentation\View\SensesView;
use Wikibase\View\EntityView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\Presentation\View\LexemeView
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Kreuz
 */
class LexemeViewTest extends \MediaWikiIntegrationTestCase {
	use HamcrestPHPUnitIntegration;

	/**
	 * @return FormsView
	 */
	private function newFormsViewMock() {
		$view = $this->createMock( FormsView::class );

		$view->method( 'getHtml' )
			->willReturn( "FormsView::getHtml\n" );

		return $view;
	}

	/**
	 * @return SensesView
	 */
	private function newSensesViewMock() {
		$view = $this->createMock( SensesView::class );

		$view->method( 'getHtml' )
			->willReturn( "SensesView::getHtml\n" );

		return $view;
	}

	/**
	 * @param StatementList|null $expectedStatements
	 *
	 * @return StatementSectionsView
	 */
	private function newStatementSectionsViewMock( StatementList $expectedStatements = null ) {
		$statementSectionsView = $this->createMock( StatementSectionsView::class );

		$statementSectionsView->expects( $expectedStatements ? $this->once() : $this->never() )
			->method( 'getHtml' )
			->with( $expectedStatements )
			->willReturn( "StatementSectionsView::getHtml\n" );

		return $statementSectionsView;
	}

	/**
	 * @return LanguageDirectionalityLookup
	 */
	private function newLanguageDirectionalityLookupMock() {
		$languageDirectionalityLookup = $this->createMock( LanguageDirectionalityLookup::class );
		$languageDirectionalityLookup->method( 'getDirectionality' )
			->willReturn( 'auto' );

		return $languageDirectionalityLookup;
	}

	private function newLexemeView( StatementList $expectedStatements = null ) {
		$languageDirectionalityLookup = $this->newLanguageDirectionalityLookupMock();

		$lemmaFormatter = new LexemeTermFormatter( '/' );

		$linkFormatter = $this->createMock( EntityIdFormatter::class );
		$linkFormatter->method( 'formatEntityId' )
			->willReturnCallback( static function ( EntityId $entityId ) {
				$id = $entityId->getSerialization();
				$label = 'LABEL OF ' . $id;
				return "<a href='foobar/$id'>$label</a>";
			} );

		return new LexemeView(
			TemplateFactory::getDefaultInstance(),
			$languageDirectionalityLookup,
			'en',
			$this->newFormsViewMock(),
			$this->newSensesViewMock(),
			$this->newStatementSectionsViewMock( $expectedStatements ),
			$lemmaFormatter,
			$linkFormatter
		);
	}

	public function testInstantiate() {
		$view = $this->newLexemeView();
		$this->assertInstanceOf( LexemeView::class, $view );
		$this->assertInstanceOf( EntityView::class, $view );
	}

	public function testGetContent_invalidEntityType() {
		$view = $this->newLexemeView();

		/** @var EntityDocument $entity */
		$entity = $this->createMock( EntityDocument::class );

		$this->expectException( InvalidArgumentException::class );
		$view->getContent( $entity );
	}

	/**
	 * @dataProvider provideTestGetContent
	 */
	public function testGetContent( Lexeme $lexeme ) {
		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getContent( $lexeme )->getHtml();
		$this->assertIsString( $html );
		$this->assertStringContainsString(
			'id="wb-lexeme-' . ( $lexeme->getId() ?: 'new' ) . '"',
			$html
		);
		$this->assertStringContainsString( 'class="wikibase-entityview wb-lexeme"', $html );
		$this->assertStringContainsString( 'FormsView::getHtml', $html );
		$this->assertStringContainsString( 'SensesView::getHtml', $html );
		$this->assertStringContainsString( 'StatementSectionsView::getHtml', $html );
	}

	public function provideTestGetContent() {
		$lexemeId = new LexemeId( 'L1' );
		$lexicalCategory = new ItemId( 'Q32' );
		$language = new ItemId( 'Q11' );
		$statements = new StatementList(
			new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ) )
		);

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
		$entity = $this->createMock( EntityDocument::class );
		$this->expectException( ParameterTypeException::class );
		$view->getTitleHtml( $entity );
	}

	public function testGetContent_containsHeaderWithLemmasAndTheirLanguages() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );
		$lemmas = new TermList( [ new Term( 'en', 'foo' ), new Term( 'en-GB', 'bar' ) ] );
		$lexeme = new Lexeme( $lexemeId, $lemmas, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );
		$html = $view->getContent( $lexeme )->getHtml();

		$this->assertIsString( $html );
		$this->assertThatHamcrest(
			$html,
			is(
				htmlPiece(
					havingChild(
						both( withClass( 'lemma-widget_lemma-list' ) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<span class="lemma-widget_lemma-value" lang="en"/>' )
								)->andAlso(
									havingTextContents( containsString( 'foo' ) )
								)
							) )
							->andAlso( havingChild(
								both(
									tagMatchingOutline( '<span class="lemma-widget_lemma-value" lang="en-GB"/>' )
								)->andAlso(
									havingTextContents( containsString( 'bar' ) )
								)
							) )
					)
				)
			)
		);
	}

	public function testGetContentForLanguage() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );

		$lexeme = new Lexeme( $lexemeId, null, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getContent( $lexeme )->getHtml();
		$this->assertIsString( $html );
		$this->assertThatHamcrest(
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
		$this->assertStringContainsString(
			'<div id="toc"></div>'
			. "StatementSectionsView::getHtml\n"
			. "SensesView::getHtml\n"
			. "FormsView::getHtml\n"
			. '</div>',
			$html
		);
	}

	public function testGetContentForLexicalCategory() {
		$lexemeId = new LexemeId( 'L1' );
		$language = new ItemId( 'Q2' );
		$lexicalCategory = new ItemId( 'Q3' );

		$lexeme = new Lexeme( $lexemeId, null, $lexicalCategory, $language );

		$view = $this->newLexemeView( $lexeme->getStatements() );

		$html = $view->getContent( $lexeme )->getHtml();
		$this->assertIsString( $html );
		$this->assertThatHamcrest(
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
		$this->assertStringContainsString(
			'<div id="toc"></div>'
			. "StatementSectionsView::getHtml\n"
			. "SensesView::getHtml\n"
			. "FormsView::getHtml\n"
			. '</div>',
			$html
		);
	}

}

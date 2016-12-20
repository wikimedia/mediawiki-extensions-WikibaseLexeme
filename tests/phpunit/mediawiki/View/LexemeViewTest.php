<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
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
 */
class LexemeViewTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return StatementSectionsView
	 */
	private function newStatementSectionsViewMock() {
		return $this->getMockBuilder( StatementSectionsView::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return EntityTermsView
	 */
	private function newEntityTermsViewMock() {
		return $this->getMockBuilder( EntityTermsView::class )
			->disableOriginalConstructor()
			->getMock();
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

	private function newLexemeView(
		$contentLanguageCode = 'en',
		EntityTermsView $entityTermsView = null,
		StatementSectionsView $statementSectionsView = null
	) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		if ( !$entityTermsView ) {
			$entityTermsView = $this->newEntityTermsViewMock();
		}

		if ( !$statementSectionsView ) {
			$statementSectionsView = $this->newStatementSectionsViewMock();
		}

		$languageDirectionalityLookup = $this->newLanguageDirectionalityLookupMock();
		$htmlTermRenderer = new FallbackHintHtmlTermRenderer(
			$languageDirectionalityLookup,
			new LanguageNameLookup( $contentLanguageCode )
		);

		return new LexemeView(
			$templateFactory,
			$entityTermsView,
			$statementSectionsView,
			$languageDirectionalityLookup,
			$htmlTermRenderer,
			$contentLanguageCode
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
	public function testGetHtml(
		Lexeme $entity,
		LexemeId $entityId = null,
		$contentLanguageCode = 'en',
		StatementList $statements = null
	) {
		$entityTermsView = $this->newEntityTermsViewMock();
		// TODO: Change this to Lexical category and language later
		$entityTermsView
			->method( 'getHtml' )
			->with(
				$contentLanguageCode,
				$entity,
				$entity,
				null,
				$entityId
			)
			->will( $this->returnValue( 'entityTermsView->getHtml' ) );

		$entityTermsView->expects( $this->never() )
			->method( 'getEntityTermsForLanguageListView' );

		$statementSectionsView = $this->newStatementSectionsViewMock();
		$statementSectionsView
			->method( 'getHtml' )
			->with(
				$this->callback( function( StatementList $statementList ) use ( $statements ) {
					return $statements ? $statementList === $statements : $statementList->isEmpty();
				} )
			)
			->will( $this->returnValue( 'statementSectionsView->getHtml' ) );

		$view = $this->newLexemeView(
			$contentLanguageCode,
			$entityTermsView,
			$statementSectionsView
		);

		$result = $view->getHtml( $entity );
		$this->assertInternalType( 'string', $result );
		$this->assertContains( 'wb-lexeme', $result );

	}

	public function provideTestGetHtml() {
		$lexemeId = new LexemeId( 'L1' );
		$statements = new StatementList( [
			new Statement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) )
		] );

		return [
			[
				new Lexeme()
			],
			[
				new Lexeme( $lexemeId ),
				$lexemeId
			],
			[
				new Lexeme( $lexemeId, null, null, null, $statements ),
				$lexemeId,
				'en',
				$statements
			],
			[
				new Lexeme( $lexemeId ),
				$lexemeId,
				'lkt'
			],
			[
				new Lexeme( $lexemeId, null, null, null, $statements ),
				$lexemeId,
				'lkt',
				$statements
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

}

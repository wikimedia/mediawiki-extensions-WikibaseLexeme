<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use MediaWikiLangTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit4And6Compat;
use Title;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Formatters\FormIdHtmlFormatter;
use Wikibase\Lexeme\Formatters\RedirectedLexemeSubEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Formatters\FormIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class FormIdHtmlFormatterTest extends MediaWikiLangTestCase {

	use PHPUnit4And6Compat;

	/**
	 * @var EntityRevisionLookup|MockObject
	 */
	private $revisionLookup;

	/**
	 * @var EntityTitleLookup|MockObject
	 */
	private $titleLookup;

	/**
	 * @var LocalizedTextProvider|MockObject
	 */
	private $textProvider;

	/**
	 * @var RedirectedLexemeSubEntityIdHtmlFormatter|MockObject
	 */
	private $redirectedLexemeSubEntityIdHtmlFormatter;

	public function setUp() {
		parent::setUp();

		$this->revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->titleLookup = $this->createMock( EntityTitleLookup::class );
		$this->textProvider = $this->getMockTextProvider();
		$this->redirectedLexemeSubEntityIdHtmlFormatter =
			$this->createMock( RedirectedLexemeSubEntityIdHtmlFormatter::class );
	}

	/**
	 * @param FormId $expectedFormId
	 * @return MockObject|EntityTitleLookup
	 */
	private function makeTitleLookupReturnMainPage( FormId $expectedFormId ) {
		$title = $this->createMock( Title::class );
		$title->method( 'isLocal' )->willReturn( true );
		$title->method( 'getLinkUrl' )->willReturn( 'LOCAL-URL#FORM' );

		$this->titleLookup->method( 'getTitleForId' )
			->with( $this->equalTo( $expectedFormId ) )
			->willReturn( $title );
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|LocalizedTextProvider
	 */
	private function getMockTextProvider() {
		$mock = $this->createMock( LocalizedTextProvider::class );
		$mock->method( 'get' )
			->willReturn( '-S-' );
		return $mock;
	}

	public function testNonExistingFormatterIsCalledForNonExistingIds_noRevision() {
		$formId = new FormId( 'L999-F666' );

		/** @var EntityRevisionLookup|MockObject $revisionLookup */
		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( null );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'L999-F666 <span class="wb-entity-undefinedinfo">(Deleted Form)</span>',
			$result
		);
	}

	public function testRedirectedLexemeSubEntityIdHtmlFormatterIsCalledForRedirectedLexemes() {
		$formId = new FormId( 'L999-F666' );

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willThrowException(
				new UnresolvedEntityRedirectException(
					$formId,
					new LexemeId( 'L1000' )
				)
			);

		$this->redirectedLexemeSubEntityIdHtmlFormatter
			->expects( $this->once() )
			->method( 'formatEntityId' )
			->with( $formId )
			->willReturn( '<a href="http://url.for/Lexeme:L999#L999-F666">L999-F666</a>' );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'<a href="http://url.for/Lexeme:L999#L999-F666">L999-F666</a>',
			$result
		);
	}

	public function testNonExistingFormatterIsCalledForNonExistingIds_noTitle() {
		$formId = new FormId( 'L999-F666' );

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( new EntityRevision(
				new Form( $formId, new TermList( [ new Term( 'en', 'a' ) ] ), []
				) ) );

		$this->titleLookup->method( 'getTitleForId' )
			->with( $this->equalTo( $formId ) )
			->willReturn( null );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'L999-F666 <span class="wb-entity-undefinedinfo">(Deleted Form)</span>',
			$result
		);
	}

	public function testFormatId_oneRepresentation() {
		$formId = new FormId( 'L999-F666' );

		$formRevision = new EntityRevision(
			new Form( $formId, new TermList( [ new Term( 'pt', 'fOo' ) ] ), [] )
		);

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( $formRevision );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'<a href="LOCAL-URL#FORM">fOo</a>',
			$result
		);
	}

	public function testFormatId_multipleRepresentations() {
		$formId = new FormId( 'L999-F666' );

		$representations = new TermList( [ new Term( 'pt', 'fOo' ), new Term( 'en', 'bAr' ) ] );
		$formRevision = new EntityRevision(
			new Form( $formId, $representations, [] )
		);

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( $formRevision );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'<a href="LOCAL-URL#FORM">fOo-S-bAr</a>',
			$result
		);
	}

	public function testFormatId_htmlEscapesRepresentation() {
		$formId = new FormId( 'L999-F666' );

		$formRevision = new EntityRevision( new Form( $formId, new TermList( [
			new Term( 'pt', '<script>alert("hi")</script>' ),
		] ), [] ) );

		$this->revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( $formRevision );

		$this->makeTitleLookupReturnMainPage( $formId );

		$formatter = $this->newFormIdHtmlFormatter();
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'<a href="LOCAL-URL#FORM">&lt;script>alert("hi")&lt;/script></a>',
			$result
		);
	}

	private function newFormIdHtmlFormatter() : FormIdHtmlFormatter {
		return new FormIdHtmlFormatter(
			$this->revisionLookup,
			$this->titleLookup,
			$this->textProvider,
			$this->redirectedLexemeSubEntityIdHtmlFormatter
		);
	}

}

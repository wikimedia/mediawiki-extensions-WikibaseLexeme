<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\PropertyType;

use MediaWikiLangTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\PropertyType\FormIdHtmlFormatter;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\PropertyType\FormIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class FormIdHtmlFormatterTest extends MediaWikiLangTestCase {

	/**
	 * @param FormId $expectedFormId
	 * @return MockObject|EntityTitleLookup
	 */
	private function getTitleLookupReturningMainPage( FormId $expectedFormId ) {
		$title = $this->getMock( 'Title' );
		$title->method( 'isLocal' )->willReturn( true );
		$title->method( 'getLinkUrl' )->willReturn( 'LOCAL-URL#FORM' );

		/** @var EntityTitleLookup|MockObject $titleLookup */
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->method( 'getTitleForId' )
			->with( $this->equalTo( $expectedFormId ) )
			->willReturn( $title );
		return $titleLookup;
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|LocalizedTextProvider
	 */
	private function getMockTextProvider() {
		$mock = $this->getMock( LocalizedTextProvider::class );
		$mock->method( 'get' )
			->willReturn( '-S-' );
		return $mock;
	}

	public function testNonExistingFormatterIsCalledForNonExistingIds_noRevision() {
		$formId = new FormId( 'L999-F666' );

		/** @var EntityRevisionLookup|MockObject $revisionLookup */
		$revisionLookup = $this->getMock( EntityRevisionLookup::class );
		$revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( null );

		$titleLookup = $this->getTitleLookupReturningMainPage( $formId );

		$formatter = new FormIdHtmlFormatter(
			$revisionLookup,
			$titleLookup,
			$this->getMockTextProvider()
		);
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'L999-F666 <span class="wb-entity-undefinedinfo">(Deleted Form)</span>',
			$result
		);
	}

	public function testNonExistingFormatterIsCalledForNonExistingIds_noTitle() {
		$formId = new FormId( 'L999-F666' );

		/** @var EntityRevisionLookup|MockObject $revisionLookup */
		$revisionLookup = $this->getMock( EntityRevisionLookup::class );
		$revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( new EntityRevision(
				new Form( $formId, new TermList( [ new Term( 'en', 'a' ) ] ), []
				) ) );

		/** @var EntityTitleLookup|MockObject $titleLookup */
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->method( 'getTitleForId' )
			->with( $this->equalTo( $formId ) )
			->willReturn( null );

		$formatter = new FormIdHtmlFormatter(
			$revisionLookup,
			$titleLookup,
			$this->getMockTextProvider()
		);
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

		/** @var EntityRevisionLookup|MockObject $revisionLookup */
		$revisionLookup = $this->getMock( EntityRevisionLookup::class );
		$revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( $formRevision );

		$titleLookup = $this->getTitleLookupReturningMainPage( $formId );

		$formatter = new FormIdHtmlFormatter(
			$revisionLookup,
			$titleLookup,
			$this->getMockTextProvider()
		);
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

		/** @var EntityRevisionLookup|MockObject $revisionLookup */
		$revisionLookup = $this->getMock( EntityRevisionLookup::class );
		$revisionLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( $formRevision );

		$titleLookup = $this->getTitleLookupReturningMainPage( $formId );

		$formatter = new FormIdHtmlFormatter(
			$revisionLookup,
			$titleLookup,
			$this->getMockTextProvider()
		);
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame(
			'<a href="LOCAL-URL#FORM">fOo-S-bAr</a>',
			$result
		);
	}

}

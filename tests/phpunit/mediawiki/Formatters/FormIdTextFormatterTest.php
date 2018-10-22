<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Formatters\FormIdTextFormatter;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Formatters\FormIdTextFormatter
 *
 * @license GPL-2.0-or-later
 */
class FormIdTextFormatterTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|LocalizedTextProvider
	 */
	private function getMockTextProvider() {
		$mock = $this->getMock( LocalizedTextProvider::class );
		$mock->method( 'get' )
			->willReturn( '-S-' );
		return $mock;
	}

	public function testNonExistingFormatterIsCalledForNonExistingIds() {
		$formId = new FormId( 'L999-F666' );

		/** @var EntityRevisionLookup|MockObject $mockLookup */
		$mockLookup = $this->getMock( EntityRevisionLookup::class );
		$mockLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( null );

		$formatter = new FormIdTextFormatter( $mockLookup, $this->getMockTextProvider() );
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame( 'L999-F666', $result );
	}

	public function testFormatEntityIdReturnsPlainFormIdForRedirectedLexeme() {
		$formId = new FormId( 'L999-F666' );

		/** @var EntityRevisionLookup|MockObject $mockLookup */
		$mockLookup = $this->getMock( EntityRevisionLookup::class );
		$mockLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willThrowException(
				new UnresolvedEntityRedirectException(
					$formId,
					new LexemeId( 'L1000' )
				)
			);

		$textProvider = $this->createMock( LocalizedTextProvider::class );
		$textProvider->expects( $this->never() )->method( 'get' );

		$formatter = new FormIdTextFormatter( $mockLookup, $textProvider );
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame( 'L999-F666', $result );
	}

	public function testFormatId_oneRepresentation() {
		$formId = new FormId( 'L999-F666' );

		$formRevision = new EntityRevision(
			new Form( $formId, new TermList( [ new Term( 'pt', 'fOo' ) ] ), [] )
		);

		/** @var EntityRevisionLookup|MockObject $mockLookup */
		$mockLookup = $this->getMock( EntityRevisionLookup::class );
		$mockLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( $formRevision );

		$formatter = new FormIdTextFormatter( $mockLookup, $this->getMockTextProvider() );
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame( 'fOo', $result );
	}

	public function testFormatId_multipleRepresentations() {
		$formId = new FormId( 'L999-F666' );

		$representations = new TermList( [ new Term( 'pt', 'fOo' ), new Term( 'en', 'bAr' ) ] );
		$formRevision = new EntityRevision(
			new Form( $formId, $representations, [] )
		);

		/** @var EntityRevisionLookup|MockObject $mockLookup */
		$mockLookup = $this->getMock( EntityRevisionLookup::class );
		$mockLookup->method( 'getEntityRevision' )
			->with( $this->equalTo( $formId ) )
			->willReturn( $formRevision );

		$formatter = new FormIdTextFormatter( $mockLookup, $this->getMockTextProvider() );
		$result = $formatter->formatEntityId( $formId );
		$this->assertSame( 'fOo-S-bAr', $result );
	}

}

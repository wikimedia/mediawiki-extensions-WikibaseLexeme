<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use HamcrestPHPUnitIntegration;
use HtmlArmor;
use InvalidArgumentException;
use Language;
use MessageLocalizer;
use PHPUnit4And6Compat;
use RawMessage;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Hooks\Formatters\FormLinkFormatter;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;

/**
 * @covers \Wikibase\Lexeme\Hooks\Formatters\FormLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class FormLinkFormatterTest extends \PHPUnit_Framework_TestCase {

	use HamcrestPHPUnitIntegration;
	use PHPUnit4And6Compat;

	const REPRESENTATION_SEPARATOR = '/';

	public function testGetHtmlIncludesFormId() {
		$lookup = $this->newEntityLookup(
			NewForm::havingLexeme( 'L1' )->andId( 'F1' )->build()
		);

		$formatter = new FormLinkFormatter(
			$lookup,
			$this->newDefaultFormatter(),
			$this->newMessageLocalizer(),
			Language::factory( 'en' )
		);

		$formId = 'L1-F1';
		$this->assertThatHamcrest(
			$formatter->getHtml( new FormId( $formId ) ),
			is( htmlPiece( havingRootElement(
					havingTextContents( containsString( $formId ) )
			) ) )
		);
	}

	public function testGetHtmlUsesRepresentationAsLinkText() {
		$representation = 'color';
		$lookup = $this->newEntityLookup(
			NewForm::havingLexeme( 'L1' )
				->andId( 'F1' )
				->andRepresentation( 'en-gb', $representation )
				->build()
		);

		$formatter = new FormLinkFormatter(
			$lookup,
			$this->newDefaultFormatter(),
			$this->newMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertThatHamcrest(
			$formatter->getHtml( new FormId( 'L1-F1' ) ),
			is( htmlPiece( havingChild(
				both(
					tagMatchingOutline( '<span lang="en-GB"/>' )
				)->andAlso(
					havingTextContents( $representation )
				)
			) ) )
		);
	}

	public function testGivenMultipleRepresentations_getHtmlRendersAllOfThem() {
		$representationGb = 'colour';
		$representationOther = 'kolorr';
		$formId = 'L1-F1';

		$lookup = $this->newEntityLookup(
			NewForm::havingLexeme( 'L1' )
				->andId( 'F1' )
				->andRepresentation( 'en-gb', $representationGb )
				->andRepresentation( 'en-x-Q1234', $representationOther )
				->build()
		);

		$formatter = new FormLinkFormatter(
			$lookup,
			$this->newDefaultFormatter(),
			$this->newMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertThatHamcrest(
			$formatter->getHtml( new FormId( $formId ) ),
			is( htmlPiece( havingRootElement( allOf(
				havingChild(
					both(
						tagMatchingOutline( '<span lang="en-GB"/>' )
					)->andAlso(
						havingTextContents( 'colour' )
					)
				),
				havingChild(
					both(
						tagMatchingOutline( '<span lang="en-x-q1234"/>' )
					)->andAlso(
						havingTextContents( 'kolorr' )
					)
				),
				havingTextContents( equalToIgnoringWhiteSpace(
					$representationGb . self::REPRESENTATION_SEPARATOR . $representationOther . $formId
				) )
			) ) ) )
		);
	}

	public function testGetHtmlConsidersDirectionalityOfRepresentation() {
		$representation = 'رنگ';

		$lookup = $this->newEntityLookup(
			NewForm::havingLexeme( 'L1' )->andId( 'F1' )->andRepresentation( 'fa', $representation )->build()
		);

		$formatter = new FormLinkFormatter(
			$lookup,
			$this->newDefaultFormatter(),
			$this->newMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertThatHamcrest(
			$formatter->getHtml( new FormId( 'L1-F1' ) ),
			is( htmlPiece( havingChild(
				both(
					tagMatchingOutline( '<span dir="rtl" class="mw-content-rtl"></span>' )
				)->andAlso(
					havingTextContents( $representation )
				)
			) ) )
		);
	}

	public function testGivenNotExistingForm_getHtmlRendersId() {
		$lookup = $this->newEntityLookup( null );

		$formatter = new FormLinkFormatter(
			$lookup,
			$this->newDefaultFormatter(),
			$this->newMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertEquals(
			'<span lang="en">L1-F100</span>',
			$formatter->getHtml( new FormId( 'L1-F100' ) )
		);
	}

	public function testGetHtmlWrapsFormDataInSpanWithUserLanguage() {
		$formatter = new FormLinkFormatter(
			$this->createMock( EntityLookup::class ),
			$this->newDefaultFormatter(),
			$this->newMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertThatHamcrest(
			$formatter->getHtml( new FormId( 'L1-F1' ) ),
			is( htmlPiece( havingRootElement(
					tagMatchingOutline( '<span lang="en"/>' )
			) ) )
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNonFormId_getHtmlThrowsException() {
		$formatter = new FormLinkFormatter(
			$this->createMock( EntityLookup::class ),
			$this->newDefaultFormatter(),
			$this->newMessageLocalizer(),
			Language::factory( 'en' )
		);

		$formatter->getHtml( new LexemeId( 'L1' ) );
	}

	public function testGetTitleAttributeReturnsFormIdFromTitleFragment() {
		$title = $this->createMock( Title::class );
		$title->method( 'getFragment' )->willReturn( 'L2-F3' );

		$formatter = new FormLinkFormatter(
			$this->createMock( EntityLookup::class ),
			$this->newDefaultFormatter(),
			$this->newMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertEquals( 'L2-F3', $formatter->getTitleAttribute( $title ) );
	}

	/**
	 * @return DefaultEntityLinkFormatter
	 */
	private function newDefaultFormatter() {
		$formatter = $this->getMockBuilder( DefaultEntityLinkFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$formatter->method( 'getHtml' )
			->willReturnCallback( function ( EntityId $entityId, array $labelData ) {
				return "<span lang=\"${labelData['language']}\">"
					. HtmlArmor::getHtml( $labelData['value'] )
					. $entityId->getSerialization()
					. '</span>';
			} );

		return $formatter;
	}

	/**
	 * @return MessageLocalizer
	 */
	private function newMessageLocalizer() {
		$localizer = $this->createMock( MessageLocalizer::class );
		$localizer->method( 'msg' )
			->willReturn( new RawMessage( self::REPRESENTATION_SEPARATOR ) );

		return $localizer;
	}

	/**
	 * @param Form|null $returnValue
	 * @return EntityLookup
	 */
	private function newEntityLookup( $returnValue ) {
		$lookup = $this->createMock( EntityLookup::class );
		$lookup->method( 'getEntity' )
			->willReturn( $returnValue );
		return $lookup;
	}

}

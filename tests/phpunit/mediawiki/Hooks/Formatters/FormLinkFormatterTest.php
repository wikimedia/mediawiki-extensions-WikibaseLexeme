<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use HamcrestPHPUnitIntegration;
use HtmlArmor;
use InvalidArgumentException;
use Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit4And6Compat;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Formatters\LexemeTermFormatter;
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

	/** @var MockObject|EntityLookup */
	private $entityLookup;

	/** @var MockObject|LexemeTermFormatter */
	private $representationsFormatter;

	public function setUp() {
		parent::setUp();

		$this->representationsFormatter = $this->createMock( LexemeTermFormatter::class );
		$this->entityLookup = $this->createMock( EntityLookup::class );
	}

	public function testGetHtml() {
		$form = NewForm::havingId( new FormId( 'L111-F222' ) )
			->andRepresentation( 'en-gb', 'potato' )
			->build();
		$this->entityLookup = $this->newEntityLookup( $form );
		$representationText = '[REPRESENTATION_TEXT]';
		$representationHtml = "<span id=\"representationText\">$representationText</span>";
		$this->representationsFormatter
			->expects( $this->once() )
			->method( 'format' )
			->with( $form->getRepresentations() )
			->willReturn( $representationHtml );

		$formatter = $this->newFormatter();

		$this->assertThatHamcrest(
			$formatter->getHtml( $form->getId() ),
			is( htmlPiece(
				havingRootElement( allOf(
					tagMatchingOutline( '<span lang="en"></span>' ),
					havingChild( both( tagMatchingOutline( '<span id="representationText">' ) )
						->andAlso( havingTextContents( $representationText ) ) ),
					havingTextContents( equalToIgnoringWhiteSpace(
						$representationText . $form->getId()->getSerialization()
					) )
				) )
			) )
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNonFormId_getHtmlThrowsException() {
		$formatter = $this->newFormatter();

		$formatter->getHtml( new LexemeId( 'L1' ) );
	}

	public function testGetTitleAttributeReturnsFormIdFromTitleFragment() {
		$title = $this->createMock( Title::class );
		$title->method( 'getFragment' )->willReturn( 'L2-F3' );

		$formatter = $this->newFormatter();

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
	 * @param Form|null $returnValue
	 * @return EntityLookup
	 */
	private function newEntityLookup( $returnValue ) {
		$lookup = $this->createMock( EntityLookup::class );
		$lookup->method( 'getEntity' )
			->willReturn( $returnValue );
		return $lookup;
	}

	private function newFormatter() : FormLinkFormatter {
		return new FormLinkFormatter(
			$this->entityLookup,
			$this->newDefaultFormatter(),
			$this->representationsFormatter,
			Language::factory( 'en' )
		);
	}

}

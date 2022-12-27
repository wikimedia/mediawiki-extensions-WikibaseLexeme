<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use HamcrestPHPUnitIntegration;
use HtmlArmor;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\FormLinkFormatter;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\FormLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class FormLinkFormatterTest extends TestCase {

	use HamcrestPHPUnitIntegration;

	/** @var MockObject|EntityLookup */
	private $entityLookup;

	/** @var MockObject|LexemeTermFormatter */
	private $representationsFormatter;

	protected function setUp(): void {
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

	public function testGivenNonFormId_getHtmlThrowsException() {
		$formatter = $this->newFormatter();

		$this->expectException( InvalidArgumentException::class );
		$formatter->getHtml( new LexemeId( 'L1' ) );
	}

	public function testGetTitleAttribute() {
		$entityId = new FormId( 'L2-F3' );
		$formatter = $this->newFormatter();
		$this->assertEquals( 'L2-F3', $formatter->getTitleAttribute( $entityId ) );
	}

	/**
	 * @dataProvider provideFragments
	 */
	public function testGetFragment( FormId $formId, $fragment, $expected ) {
		$formatter = $this->newFormatter();

		$actual = $formatter->getFragment( $formId, $fragment );

		$this->assertSame( $expected, $actual );
	}

	public function provideFragments() {
		$f1 = new FormId( 'L1-F1' );
		return [
			'old-style' => [ $f1, 'L1-F1', 'F1' ],
			'new-style' => [ $f1, 'F1', 'F1' ],
			'old-style, wrong lexeme' => [ $f1, 'L2-F1', 'L2-F1' ],
		];
	}

	/**
	 * @return DefaultEntityLinkFormatter
	 */
	private function newDefaultFormatter() {
		$formatter = $this->createMock( DefaultEntityLinkFormatter::class );

		$formatter->method( 'getHtml' )
			->willReturnCallback( static function ( EntityId $entityId, array $labelData ) {
				return "<span lang=\"{$labelData['language']}\">"
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

	private function newFormatter(): FormLinkFormatter {
		return new FormLinkFormatter(
			$this->entityLookup,
			$this->newDefaultFormatter(),
			$this->representationsFormatter,
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' )
		);
	}

}

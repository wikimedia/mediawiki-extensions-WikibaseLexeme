<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use HamcrestPHPUnitIntegration;
use HtmlArmor;
use Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\LexemeLinkFormatter;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\LexemeLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class LexemeLinkFormatterTest extends TestCase {

	use HamcrestPHPUnitIntegration;
	use PHPUnit4And6Compat;

	/** @var MockObject|EntityLookup */
	private $entityLookup;

	/** @var MockObject|LexemeTermFormatter */
	private $lemmaFormatter;

	public function setUp() {
		parent::setUp();

		$this->lemmaFormatter = $this->createMock( LexemeTermFormatter::class );
		$this->entityLookup = $this->createMock( EntityLookup::class );
	}

	/**
	 * @dataProvider notALexemeProvider
	 */
	public function testGivenNotALexemeId_getHtmlThrowsException( EntityId $id ) {
		$formatter = $this->newFormatter();
		$this->setExpectedException( ParameterTypeException::class );
		$formatter->getHtml( $id, [] );
	}

	public function notALexemeProvider() {
		return [
			[ new ItemId( 'Q123' ) ],
			[ new FormId( 'L12-F3' ) ],
		];
	}

	public function testGetHtml() {
		$lexeme = NewLexeme::havingId( 'L2' )->withLemma( 'en-gb', 'potato' )->build();
		$this->entityLookup = $this->getMockEntityLookup( $lexeme );
		$lemmaHtml = '[LEMMA_HTML]';
		$this->lemmaFormatter
			->expects( $this->once() )
			->method( 'format' )
			->with( $lexeme->getLemmas() )
			->willReturn( $lemmaHtml );

		$formatter = $this->newFormatter();

		$this->assertThatHamcrest( $formatter->getHtml( new LexemeId( 'L2' ) ),
			is( htmlPiece(
				havingRootElement(
					both( tagMatchingOutline( '<span lang="en"></span>' ) )
						->andAlso( havingTextContents( equalToIgnoringWhiteSpace( $lemmaHtml . 'L2' ) ) )
				)
			) )
		);
	}

	public function testGivenLexemeDoesNotExist_formatsWithoutLemmas() {
		$this->entityLookup = $this->getMockEntityLookup( null );
		$formatterOutput = '[FORMATTER_OUTPUT]';
		$this->lemmaFormatter
			->expects( $this->once() )
			->method( 'format' )
			->with( new TermList() )
			->willReturn( $formatterOutput );

		$formatter = $this->newFormatter();

		$this->assertContains( $formatterOutput, $formatter->getHtml( new LexemeId( 'L1' ) ) );
	}

	private function newFormatter() {
		return new LexemeLinkFormatter(
			$this->entityLookup,
			$this->getMockDefaultFormatter(),
			$this->lemmaFormatter,
			Language::factory( 'en' )
		);
	}

	/**
	 * @param Lexeme|null $lexeme
	 * @return \PHPUnit_Framework_MockObject_MockObject|EntityLookup
	 */
	private function getMockEntityLookup( $lexeme ) {
		$entityLookup = $this->getMock( EntityLookup::class );
		$entityLookup->method( 'getEntity' )->willReturn( $lexeme );

		return $entityLookup;
	}

	public function testGetTitleAttribute() {
		$formatter = $this->newFormatter();
		$title = $this->getMock( Title::class );
		$title->method( 'getPrefixedText' )->willReturn( 'Lexeme:L123' );
		$this->assertEquals(
			'Lexeme:L123',
			$formatter->getTitleAttribute( $title )
		);
	}

	/**
	 * @return EntityLinkFormatter|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getMockDefaultFormatter() {
		$formatter = $this->getMockBuilder( DefaultEntityLinkFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$formatter->method( 'getHtml' )
			->willReturnCallback( function ( EntityId $entityId, array $label ) {
				return "<span lang=\"${label['language']}\">"
					. HtmlArmor::getHtml( $label['value'] )
					. $entityId->getSerialization()
					. '</span>';
			} );

		return $formatter;
	}

}

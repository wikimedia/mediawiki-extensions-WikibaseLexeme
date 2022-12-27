<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use HamcrestPHPUnitIntegration;
use HtmlArmor;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataAccess\Store\EntityLookupLemmaLookup;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\MediaWiki\EntityLinkFormatters\LexemeLinkFormatter;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityTitleTextLookup;
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

	/** @var MockObject|EntityLookup */
	private $entityLookup;

	/** @var MockObject|LexemeTermFormatter */
	private $lemmaFormatter;

	protected function setUp(): void {
		parent::setUp();

		$this->lemmaFormatter = $this->createMock( LexemeTermFormatter::class );
		$this->entityLookup = $this->createMock( EntityLookup::class );
	}

	/**
	 * @dataProvider notALexemeProvider
	 */
	public function testGivenNotALexemeId_getHtmlThrowsException( EntityId $id ) {
		$formatter = $this->newFormatter();
		$this->expectException( ParameterTypeException::class );
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

		$this->assertStringContainsString(
			$formatterOutput,
			$formatter->getHtml( new LexemeId( 'L1' ) )
		);
	}

	private function newFormatter( $titleText = 'foo' ) {
		return new LexemeLinkFormatter(
			$this->getEntityTitleTextLookupMock( $titleText ),
			new EntityLookupLemmaLookup( $this->entityLookup ),
			$this->getMockDefaultFormatter(),
			$this->lemmaFormatter,
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' )
		);
	}

	/**
	 * @param Lexeme|null $lexeme
	 * @return MockObject|EntityLookup
	 */
	private function getMockEntityLookup( $lexeme ) {
		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->method( 'getEntity' )->willReturn( $lexeme );

		return $entityLookup;
	}

	public function testGetTitleAttribute() {
		$lexemeId = new LexemeId( 'L123' );
		$titleText = 'Lexeme:L123';
		$formatter = $this->newFormatter( $titleText );
		$this->assertEquals(
			$titleText,
			$formatter->getTitleAttribute( $lexemeId )
		);
	}

	/**
	 * @group regression
	 * For T228996
	 */
	public function testGetHtml_whenDoubleRedirectLexemes_usesLexemeID() {
		$unresolvedRedirectionException = new UnresolvedEntityRedirectException(
			new LexemeId( 'Lexeme:L123' ), new LexemeId( 'Lexeme:L234' ) );
		$this->entityLookup->method( 'getEntity' )
			->will( $this->throwException( $unresolvedRedirectionException ) );

		$formatter = $this->newFormatter();

		$this->assertStringContainsString(
			'Lexeme:L123',
			$formatter->getHtml( new LexemeId( 'Lexeme:L123' ) )
		);
	}

	/**
	 * @return EntityLinkFormatter|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getMockDefaultFormatter() {
		$formatter = $this->createMock( DefaultEntityLinkFormatter::class );

		$formatter->method( 'getHtml' )
			->willReturnCallback( static function ( EntityId $entityId, array $label ) {
				return "<span lang=\"{$label['language']}\">"
					. HtmlArmor::getHtml( $label['value'] )
					. $entityId->getSerialization()
					. '</span>';
			} );

		return $formatter;
	}

	private function getEntityTitleTextLookupMock( $titleText ) {
		$entityTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );
		$entityTitleTextLookup->method( 'getPrefixedText' )
			->with( $entityId ?? $this->anything() )
			->willReturn( $titleText );
		return $entityTitleTextLookup;
	}

}

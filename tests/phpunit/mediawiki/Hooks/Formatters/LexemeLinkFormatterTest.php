<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Hooks\Formatters;

use HamcrestPHPUnitIntegration;
use HtmlArmor;
use Language;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Hooks\Formatters\LexemeLinkFormatter;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers Wikibase\Lexeme\Hooks\Formatters\LexemeLinkFormatter
 *
 * @license GPL-2.0-or-later
 */
class LexemeLinkFormatterTest extends TestCase {

	use HamcrestPHPUnitIntegration;
	use \PHPUnit4And6Compat;

	const LEMMA_SEPARATOR = '/';

	/**
	 * @dataProvider notALexemeProvider
	 */
	public function testGivenNotALexemeId_getHtmlThrowsException( EntityId $id ) {
		$formatter = $this->getLexemeLinkFormatter();
		$this->setExpectedException( ParameterTypeException::class );
		$formatter->getHtml( $id, [] );
	}

	/**
	 * Note that the lang and dir attributes depend on the behavior of Language::getDir
	 * and Language::getHtmlCode which is why the capitalization of the attribute values
	 * are changed from the input
	 *
	 * @see Language::getHtmlCode()
	 * @see Language::getDir()
	 */
	public function testGetHtml() {
		$lookup = $this->getMockEntityLookup(
			NewLexeme::havingId( 'L2' )->withLemma( 'en-gb', 'potato' )->build()
		);
		$formatter = new LexemeLinkFormatter(
			$lookup,
			$this->getMockDefaultFormatter(),
			$this->getMockMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertThatHamcrest( $formatter->getHtml( new LexemeId( 'L2' ) ),
			is( htmlPiece(
				havingRootElement( allOf(
					tagMatchingOutline( '<span lang="en"></span>' ),
					havingTextContents( containsString( 'L2' ) ),
					havingChild( both(
						tagMatchingOutline( '<span lang="en-GB"></span>' ) )
						->andAlso( havingTextContents( 'potato' ) )
					)
				) )
			) )
		);
	}

	/**
	 * Note that the lang and dir attributes depend on the behavior of Language::getDir
	 * and Language::getHtmlCode which is why the capitalization of the attribute values
	 * are changed from the input
	 *
	 * @see Language::getHtmlCode()
	 * @see Language::getDir()
	 */
	public function testGivenMultipleLemmas_getHtmlConcatenatesThem() {
		$lexemeId = 'L2';
		$lemma1 = 'colour';
		$lemma2 = 'color';

		$lookup = $this->getMockEntityLookup(
			NewLexeme::havingId( $lexemeId )
				->withLemma( 'en-x-Q321', $lemma1 )
				->withLemma( 'en-ca', $lemma2 )
				->build()
		);
		$formatter = new LexemeLinkFormatter(
			$lookup,
			$this->getMockDefaultFormatter(),
			$this->getMockMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertThatHamcrest( $formatter->getHtml( new LexemeId( $lexemeId ) ),
			is( htmlPiece(
				havingRootElement( allOf(
					tagMatchingOutline( '<span lang="en"></span>' ),
					havingTextContents( containsString( $lexemeId ) ),
					havingChild( both(
						tagMatchingOutline( '<span lang="en-x-q321"></span>' ) )
						->andAlso( havingTextContents( $lemma1 ) )
					),
					havingChild( both(
						tagMatchingOutline( '<span lang="en-CA"></span>' ) )
						->andAlso( havingTextContents( $lemma2 ) )
					),
					havingTextContents( equalToIgnoringWhiteSpace(
						$lemma1 . self::LEMMA_SEPARATOR . $lemma2 . $lexemeId
					) )
				) )
			) )
		);
	}

	public function testGivenLemmaInRtlLanguage_getHtmlReturnValueContainsRtlDirAttribute() {
		$lemma = 'صِفْر';
		$lookup = $this->getMockEntityLookup(
			NewLexeme::havingId( 'L12345' )
				->withLemma( 'ar', $lemma )
				->build()
		);
		$formatter = new LexemeLinkFormatter(
			$lookup,
			$this->getMockDefaultFormatter(),
			$this->getMockMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertThatHamcrest( $formatter->getHtml( new LexemeId( 'L12345' ) ),
			is( htmlPiece(
				havingChild( both(
					tagMatchingOutline( '<span dir="rtl" class="mw-content-rtl"></span>' ) )
					->andAlso( havingTextContents( $lemma ) )
				)
			) )
		);
	}

	private function getLexemeLinkFormatter() {
		return new LexemeLinkFormatter(
			$this->getMock( EntityLookup::class ),
			$this->getMockDefaultFormatter(),
			$this->getMockMessageLocalizer(),
			Language::factory( 'en' )
		);
	}

	public function notALexemeProvider() {
		return [
			[ new ItemId( 'Q123' ) ],
			[ new FormId( 'L12-F3' ) ],
		];
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
		$formatter = $this->getLexemeLinkFormatter();
		$title = $this->getMock( Title::class );
		$title->method( 'getPrefixedText' )->willReturn( 'Lexeme:L123' );
		$this->assertEquals(
			'Lexeme:L123',
			$formatter->getTitleAttribute( $title )
		);
	}

	public function testGivenUnknownLexeme_getHtmlReturnsFormattedId() {
		$formatter = new LexemeLinkFormatter(
			$this->getMockEntityLookup( null ),
			$this->getMockDefaultFormatter(),
			$this->getMockMessageLocalizer(),
			Language::factory( 'en' )
		);

		$this->assertEquals(
			'<span lang="en">L321</span>',
			$formatter->getHtml( new LexemeId( 'L321' ) )
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

	private function getMockMessageLocalizer() {
		$localizer = $this->getMock( \MessageLocalizer::class );
		$localizer->method( 'msg' )->willReturn( new \RawMessage( self::LEMMA_SEPARATOR ) );

		return $localizer;
	}

}

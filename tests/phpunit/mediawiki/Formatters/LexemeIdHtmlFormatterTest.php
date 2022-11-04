<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Presentation\Formatters\LexemeIdHtmlFormatter;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Presentation\Formatters\LexemeIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class LexemeIdHtmlFormatterTest extends TestCase {

	use HamcrestPHPUnitIntegration;

	private const SINGLE_LEMMA_LEXEME_ID = 'L313';
	private const MULTIPLE_LEMMA_LEXEME_ID = 'L323';
	private const LEMMA = 'artichoke';
	private const OTHER_LEMMA = 'garlic';
	private const LANGUAGE_ID = 'Q100';
	private const LANGUAGE = 'English';
	private const LEXICAL_CATEGORY_ID = 'Q200';
	private const LEXICAL_CATEGORY = 'noun';

	/** @var string */
	private $lemmaLanguage;

	protected function setUp(): void {
		parent::setUp();

		$this->lemmaLanguage = 'en';
	}

	public function testFormatReturnsLinkWithLexemeLemmaAsContents() {
		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( new LexemeId( self::SINGLE_LEMMA_LEXEME_ID ) );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece( havingRootElement(
				both( withTagName( 'a' ) )
					->andAlso( havingTextContents( containsString( self::LEMMA ) ) )
			) ) )
		);
	}

	/**
	 * @dataProvider lemmaLanguageProvider
	 */
	public function testFormatReturnsLinkWithLemmasWrappedInLangCodedSpans( $lemmaLang, $langAttr ) {
		$this->lemmaLanguage = $lemmaLang;
		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( new LexemeId( self::SINGLE_LEMMA_LEXEME_ID ) );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece(
				havingChild( tagMatchingOutline( '<span lang="' . $langAttr . '"/>' ) ) )
			)
		);
	}

	public function lemmaLanguageProvider() {
		yield 'BCP 47 compliant language code' => [ 'en', 'en' ];
		yield 'mediawiki language code mapped to BCP 47' => [ 'mo', 'ro-Cyrl-MD' ];
	}

	public function testFormatReturnsLinkWithLexemePageAsUrl() {
		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( new LexemeId( self::SINGLE_LEMMA_LEXEME_ID ) );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece( havingRootElement(
				tagMatchingOutline( '<a href="http://url.for/Lexeme:L313"/>' )
			) ) )
		);
	}

	public function testFormatReturnsLinkWithLexemeIdAndLanguageAndLexicalCategoryLabelsAsTitle() {
		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( new LexemeId( self::SINGLE_LEMMA_LEXEME_ID ) );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece( havingRootElement(
				tagMatchingOutline(
					'<a title="(wikibaselexeme-lexeme-link-title: L313, ' .
					'(wikibaselexeme-presentation-lexeme-secondary-label: English, noun)' .
					')"/>'
				)
			) ) )
		);
	}

	public function testGivenLexemeWithMultipleLemmas_allLemmasAreDisplayedInLink() {
		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( new LexemeId( self::MULTIPLE_LEMMA_LEXEME_ID ) );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece( havingRootElement(
				both( withTagName( 'a' ) )
					->andAlso( havingTextContents( allOf(
						containsString( self::LEMMA ),
						containsString(
							'(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)'
						),
						containsString( self::OTHER_LEMMA )
					) ) )
			) ) )
		);
	}

	public function testGivenLexemeDoesNotExist_returnsProperMessage() {
		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( new LexemeId( 'L314' ) );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece(
					havingChild( tagMatchingOutline( '<span class="wb-entity-undefinedinfo" />' ) ) )
			)
		);
	}

	public function testGivenLexicalCategoryWithoutLabel_showsItemId() {
		$lookup = $this->createMock( LabelDescriptionLookup::class );
		$lookup->method( $this->anything() )
			->willReturn( null );
		$formatter = new LexemeIdHtmlFormatter(
			$this->newEntityLookup(),
			$lookup,
			$this->newTitleLookup(),
			new DummyLocalizedTextProvider(),
			new NonExistingEntityIdHtmlFormatter(
				'wikibaselexeme-deletedentity-'
			)
		);

		$this->assertThatHamcrest(
			$formatter->formatEntityId( new LexemeId( self::SINGLE_LEMMA_LEXEME_ID ) ),
			is( htmlPiece( havingRootElement(
				both( tagMatchingOutline(
						'<a title="(wikibaselexeme-lexeme-link-title: L313, ' .
						'(wikibaselexeme-presentation-lexeme-secondary-label: Q100, Q200)' .
						')"/>' )
				)->andAlso( havingTextContents( containsString( self::LEMMA ) ) )
			) ) )
		);
	}

	// TODO: test non-lexeme-id case

	private function newFormatter() {
		return new LexemeIdHtmlFormatter(
			$this->newEntityLookup(),
			$this->newLabelDescriptionLookup(),
			$this->newTitleLookup(),
			new DummyLocalizedTextProvider(),
			new NonExistingEntityIdHtmlFormatter(
				'wikibaselexeme-deletedentity-'
			)
		);
	}

	private function newEntityLookup() {
		$lexeme = NewLexeme::havingId( self::SINGLE_LEMMA_LEXEME_ID )
			->withLanguage( self::LANGUAGE_ID )
			->withLexicalCategory( self::LEXICAL_CATEGORY_ID )
			->withLemma( $this->lemmaLanguage, self::LEMMA )
			->build();

		$otherLexeme = NewLexeme::havingId( self::MULTIPLE_LEMMA_LEXEME_ID )
			->withLemma( $this->lemmaLanguage, self::LEMMA )
			->withLemma( 'en-foo', self::OTHER_LEMMA )
			->build();

		$lookup = new InMemoryEntityLookup();
		$lookup->addEntity( $lexeme );
		$lookup->addEntity( $otherLexeme );

		return $lookup;
	}

	/**
	 * @return LabelDescriptionLookup
	 */
	private function newLabelDescriptionLookup() {
		$lookup = $this->createMock( LabelDescriptionLookup::class );

		$lookup->method( $this->anything() )
			->willReturnCallback( static function ( ItemId $id ) {
				if ( $id->getSerialization() === self::LANGUAGE_ID ) {
					return new Term( 'en', self::LANGUAGE );
				}
				return new Term( 'en', self::LEXICAL_CATEGORY );
			} );

		return $lookup;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function newTitleLookup() {
		$lookup = $this->createMock( EntityTitleLookup::class );

		$lookup->method( 'getTitleForId' )
			->willReturnCallback( function ( LexemeId $id ) {
				$title = $this->createMock( \Title::class );

				$title->method( 'isLocal' )->willReturn( true );
				$title->method( 'getLocalURL' )
					->willReturn( 'http://url.for/Lexeme:' . $id->getSerialization() );

				return $title;
			} );

		return $lookup;
	}

}

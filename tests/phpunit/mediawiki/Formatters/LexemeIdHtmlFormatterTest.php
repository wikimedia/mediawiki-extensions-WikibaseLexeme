<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Formatters\LexemeIdHtmlFormatter;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Formatters\LexemeIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class LexemeIdHtmlFormatterTest extends TestCase {

	use HamcrestPHPUnitIntegration;
	use PHPUnit4And6Compat;

	const SINGLE_LEMMA_LEXEME_ID = 'L313';
	const MULTIPLE_LEMMA_LEXEME_ID = 'L323';
	const LEMMA = 'artichoke';
	const OTHER_LEMMA = 'garlic';
	const LANGUAGE_ID = 'Q100';
	const LANGUAGE = 'English';
	const LEXICAL_CATEGORY_ID = 'Q200';
	const LEXICAL_CATEGORY = 'noun';

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

	public function testFormatReturnsLinkWithLexemeLemmasWrappedInLangCodedSpans() {
		$formatter = $this->newFormatter();
		$formattedId = $formatter->formatEntityId( new LexemeId( self::SINGLE_LEMMA_LEXEME_ID ) );

		$this->assertThatHamcrest(
			$formattedId,
			is( htmlPiece(
				havingChild( tagMatchingOutline( '<span lang="en"/>' ) ) )
			)
		);
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

	public function testGivenLexicalCategoryWithoutLabel_showsItemId() {
		$lookup = $this->getMock( LabelDescriptionLookup::class );
		$lookup->method( $this->anything() )
			->willReturn( null );
		$formatter = new LexemeIdHtmlFormatter(
			$this->newEntityLookup(),
			$lookup,
			$this->newTitleLookup(),
			new DummyLocalizedTextProvider()
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
			new DummyLocalizedTextProvider()
		);
	}

	private function newEntityLookup() {
		$lexeme = NewLexeme::havingId( self::SINGLE_LEMMA_LEXEME_ID )
			->withLanguage( self::LANGUAGE_ID )
			->withLexicalCategory( self::LEXICAL_CATEGORY_ID )
			->withLemma( 'en', self::LEMMA )
			->build();

		$otherLexeme = NewLexeme::havingId( self::MULTIPLE_LEMMA_LEXEME_ID )
			->withLemma( 'en', self::LEMMA )
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
		$lookup = $this->getMock( LabelDescriptionLookup::class );

		$lookup->method( $this->anything() )
			->willReturnCallback( function ( ItemId $id ) {
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
		$lookup = $this->getMock( EntityTitleLookup::class );

		$lookup->method( 'getTitleForId' )
			->willReturnCallback( function ( LexemeId $id ) {
				$title = $this->getMock( \Title::class );

				$title->method( 'isLocal' )->willReturn( true );
				$title->method( 'getLocalURL' )
					->willReturn( 'http://url.for/Lexeme:' . $id->getSerialization() );

				return $title;
			} );

		return $lookup;
	}

}

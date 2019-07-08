<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\Storage\SenseLabelDescriptionLookup;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Domain\Storage\SenseLabelDescriptionLookup
 *
 * @license GPL-2.0-or-later
 */
class SenseLabelDescriptionLookupTest extends TestCase {

	/**
	 * @dataProvider provideLemmasAndExpectedLanguageCodesAndTexts
	 */
	public function testGetLabel( $lemmas, $expectedLanguageCode, $expectedText ) {
		$entityLookup = new InMemoryEntityLookup();
		$labelDescriptionLookup = new SenseLabelDescriptionLookup(
			$entityLookup,
			new LanguageFallbackChain( [] ),
			new DummyLocalizedTextProvider()
		);

		$sense = NewSense::havingGloss( 'en', 'dictionary form of a word' )
			->build();
		$lexemeBuilder = NewLexeme::havingId( 'L1' )
			->withSense( $sense );
		foreach ( $lemmas as list( $languageCode, $text ) ) {
			$lexemeBuilder = $lexemeBuilder->withLemma( $languageCode, $text );
		}
		$lexeme = $lexemeBuilder->build();
		$entityLookup->addEntity( $lexeme );

		$label = $labelDescriptionLookup->getLabel( $sense->getId() );

		$this->assertSame( $expectedLanguageCode, $label->getLanguageCode() );
		$this->assertSame( $expectedText, $label->getText() );
	}

	public function provideLemmasAndExpectedLanguageCodesAndTexts() {
		yield 'one lemma' => [
			[ [ 'en', 'lemma' ] ],
			'en',
			'lemma',
		];

		yield 'three lemmas' => [
			[
				[ 'en', 'lemma' ],
				[ 'en-us', 'lemma in US' ],
				[ 'en-gb', 'lemma in GB' ],
			],
			'en',
			'lemma' .
			'(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)' .
			'lemma in US' .
			'(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)' .
			'lemma in GB',
		];
	}

	public function testGetLabel_unknownEntity() {
		$labelDescriptionLookup = new SenseLabelDescriptionLookup(
			new InMemoryEntityLookup(),
			new LanguageFallbackChain( [] ),
			new DummyLocalizedTextProvider()
		);

		$label = $labelDescriptionLookup->getLabel( new SenseId( 'L1-S1' ) );

		$this->assertNull( $label );
	}

	/**
	 * @dataProvider provideGlossesAndExpectedGloss
	 */
	public function testGetDescription( $glosses, $expectedGloss ) {
		$entityLookup = new InMemoryEntityLookup();
		$labelDescriptionLookup = new \Wikibase\Lexeme\Domain\Storage\SenseLabelDescriptionLookup(
			$entityLookup,
			new LanguageFallbackChain( [
				LanguageWithConversion::factory( 'de' ),
				LanguageWithConversion::factory( 'en' ),
			] ),
			new DummyLocalizedTextProvider()
		);

		$senseBuilder = NewSense::havingId( 'S1' );
		foreach ( $glosses as list( $languageCode, $text ) ) {
			$senseBuilder = $senseBuilder->withGloss( $languageCode, $text );
		}
		$sense = $senseBuilder->build();
		$entityLookup->addEntity( $sense );

		$description = $labelDescriptionLookup->getDescription( $sense->getId() );

		if ( $expectedGloss === null ) {
			$this->assertNull( $description );
		} else {
			$this->assertSame( $expectedGloss[0], $description->getLanguageCode() );
			$this->assertSame( $expectedGloss[1], $description->getText() );
		}
	}

	public function provideGlossesAndExpectedGloss() {
		$glossDe = [ 'de', 'Stichwortform im Wörterbuch' ];
		$glossEn = [ 'en', 'dictionary form of a word' ];
		$glossPt = [ 'pt', 'forma canônica de uma palavra' ];

		yield 'user language' => [
			[ $glossPt, $glossEn, $glossDe ],
			$glossDe,
		];

		yield 'fallback language' => [
			[ $glossPt, $glossEn ],
			$glossEn,
		];

		yield 'other language' => [
			[ $glossPt ],
			null,
		];
	}

	public function testGetDescription_unknownEntity() {
		$labelDescriptionLookup = new SenseLabelDescriptionLookup(
			new InMemoryEntityLookup(),
			new LanguageFallbackChain( [] ),
			new DummyLocalizedTextProvider()
		);

		$description = $labelDescriptionLookup->getDescription( new SenseId( 'L1-S1' ) );

		$this->assertNull( $description );
	}

}

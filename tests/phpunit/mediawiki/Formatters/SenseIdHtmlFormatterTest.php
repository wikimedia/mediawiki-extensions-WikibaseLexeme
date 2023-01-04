<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use HamcrestPHPUnitIntegration;
use MediaWiki\MediaWikiServices;
use MediaWikiLangTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\Formatters\SenseIdHtmlFormatter;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Presentation\Formatters\SenseIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class SenseIdHtmlFormatterTest extends MediaWikiLangTestCase {

	use HamcrestPHPUnitIntegration;

	private function getMockTitleLookup( SenseId $expectedSenseId ): EntityTitleLookup {
		$title = $this->createMock( Title::class );
		$title->method( 'isLocal' )->willReturn( true );
		$title->method( 'getLinkUrl' )->willReturn( 'LOCAL-URL#SENSE' );

		/** @var EntityTitleLookup|MockObject $titleLookup */
		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->method( 'getTitleForId' )
			->with( $expectedSenseId )
			->willReturn( $title );

		return $titleLookup;
	}

	private function newMockRevisionLookupWithRevision( EntityRevision $rev ): EntityRevisionLookup {
		$mock = $this->createMock( EntityRevisionLookup::class );
		$mock->method( 'getEntityRevision' )
			->willReturn( $rev );

		return $mock;
	}

	private function getMockLanguageFallbackIndicator(): LanguageFallbackIndicator {
		$mock = $this->createMock( LanguageFallbackIndicator::class );
		$mock->method( 'getHtml' )
			->willReturn( 'FB-INDICATOR' );
		return $mock;
	}

	private function getLanguageFallbackChain(): TermLanguageFallbackChain {
		return new TermLanguageFallbackChain(
			[
				LanguageWithConversion::factory( 'en' ),
				LanguageWithConversion::factory( 'fr' ),
				LanguageWithConversion::factory( 'he' ),
				LanguageWithConversion::factory( 'mo' ),
			],
			$this->getStubContentLanguages()
		);
	}

	private function getStubContentLanguages(): ContentLanguages {
		$mock = $this->createStub( ContentLanguages::class );
		$mock->method( 'hasLanguage' )
			->willReturn( true );
		return $mock;
	}

	private function getEntityIdLabelFormatter(): EntityIdFormatter {
		$formatter = $this->createStub( EntityIdFormatter::class );
		$formatter->method( 'formatEntityId' )
			->willReturnCallback( static function ( EntityId $value ): string {
				return "label of {$value->getSerialization()}";
			} );
		return $formatter;
	}

	private function getFormatter(
		SenseId $senseId,
		EntityRevisionLookup $lookup
	): SenseIdHtmlFormatter {
		return new SenseIdHtmlFormatter(
			$this->getMockTitleLookup( $senseId ),
			$lookup,
			new DummyLocalizedTextProvider(),
			$this->getLanguageFallbackChain(),
			$this->getMockLanguageFallbackIndicator(),
			MediaWikiServices::getInstance()->getLanguageFactory(),
			$this->getEntityIdLabelFormatter()
		);
	}

	public function testFormatId_nonExistingEntity() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->createMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturn( null );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame( '<a href="LOCAL-URL#SENSE">L10-S20</a>', $result );
	}

	public function testFormatId_redirectedEntity() {
		$senseId = new SenseId( 'L10-S20' );
		$exception = $this->createMock( RevisionedUnresolvedRedirectException::class );
		$lookup = $this->createMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willThrowException( $exception );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame( '<a href="LOCAL-URL#SENSE">L10-S20</a>', $result );
	}

	public function testFormatId_oneLemma_noGloss() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->newMockRevisionLookupWithRevision(
			new EntityRevision( NewLexeme::create()
				->withId( 'L10' )
				->withLemma( 'en', 'lemma' )
				->withSense( NewSense::havingId( $senseId ) )
				->build() )
		);
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$expected = '<a href="LOCAL-URL#SENSE">L10-S20</a>';
		$this->assertSame( $expected, $result );
	}

	/**
	 * @dataProvider glossLanguageProvider
	 */
	public function testFormatId_oneLemma_ownGlossLanguage(
		string $language,
		string $langAttr,
		string $dirAttr
	) {
		$senseId = new SenseId( 'L10-S20' );
		$glossText = 'gloss';
		$lemmaText = 'lemma';
		$labelText = 'label of Q123';
		$lookup = $this->newMockRevisionLookupWithRevision(
			new EntityRevision( NewLexeme::create()
				->withId( 'L10' )
				->withLemma( $language, $lemmaText )
				->withLanguage( 'Q123' )
				->withSense( NewSense::havingId( $senseId )
					->withGloss( $language, $glossText ) )
				->build() )
		);
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingDirectChild( allOf(
				tagMatchingOutline( '<a href="LOCAL-URL#SENSE">' ),
				havingChild(
					both( tagMatchingOutline(
						"<span lang='$langAttr' dir='$dirAttr'>"
					) )->andAlso(
						havingTextContents( $glossText )
					)
				),
				havingChild(
					both( tagMatchingOutline(
						"<span lang='$langAttr' dir='$dirAttr'>"
					) )->andAlso(
						havingTextContents( $lemmaText )
					)
				)
			) ) ) )
		);
		$this->assertSame(
			"(wikibaselexeme-senseidformatter-layout: $lemmaText, $glossText, $labelText)FB-INDICATOR",
			strip_tags( $result )
		);
	}

	public function glossLanguageProvider(): iterable {
		yield 'mediawiki language code mapped to BCP 47' => [ 'mo', 'ro-Cyrl-MD', 'ltr' ];
		yield 'BCP 47 compliant language code' => [ 'en', 'en', 'ltr' ];
		yield 'rtl language' => [ 'he', 'he', 'rtl' ];
	}

	public function testFormatId_threeLemmas_ownGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lemma1Language = 'en';
		$lemma1Text = 'lemma';
		$lemma2Language = 'de';
		$lemma2Text = 'Lemma';
		$lemma3Language = 'el';
		$lemma3Text = 'λεμμα';
		$lookup = $this->newMockRevisionLookupWithRevision(
			new EntityRevision( NewLexeme::create()
				->withId( 'L10' )
				->withLemma( $lemma1Language, $lemma1Text )
				->withLemma( $lemma2Language, $lemma2Text )
				->withLemma( $lemma3Language, $lemma3Text )
				->withLanguage( 'Q123' )
				->withSense( NewSense::havingId( $senseId )
					->withGloss( 'fr', 'gloss' ) )
				->build() )
		);
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( allOf(
				havingChild(
					both( tagMatchingOutline(
						"<span lang='$lemma1Language'>"
					) )->andAlso(
						havingTextContents( $lemma1Text )
					)
				),
				havingChild(
					both( tagMatchingOutline(
						"<span lang='$lemma2Language'>"
					) )->andAlso(
						havingTextContents( $lemma2Text )
					)
				),
				havingChild(
					both( tagMatchingOutline(
						"<span lang='$lemma3Language'>"
					) )->andAlso(
						havingTextContents( $lemma3Text )
					)
				)
			) ) )
		);
		$this->assertSame(
			// phpcs:ignore Generic.Files.LineLength
			"(wikibaselexeme-senseidformatter-layout: $lemma1Text(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)$lemma2Text(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)$lemma3Text, gloss, label of Q123)FB-INDICATOR",
			strip_tags( $result )
		);
	}

	public function testFormatId_oneLemma_fallbackGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->newMockRevisionLookupWithRevision(
			new EntityRevision( NewLexeme::create()
				->withId( 'L10' )
				->withLemma( 'en', 'lemma' )
				->withLanguage( 'Q123' )
				->withSense( NewSense::havingId( $senseId )
					->withGloss( 'en', 'gloss' ) )
				->build() )
		);
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$expected = '(wikibaselexeme-senseidformatter-layout: lemma, gloss, label of Q123)FB-INDICATOR';
		$this->assertSame( $expected, strip_tags( $result ) );
	}

	public function testFormatEntityId_htmlEscapesText() {
		$senseId = new SenseId( 'L999-S666' );
		$lookup = $this->newMockRevisionLookupWithRevision(
			new EntityRevision( NewLexeme::create()
				->withId( 'L999' )
				->withLemma( 'en', 'lemma' )
				->withSense( NewSense::havingId( $senseId )
					->withGloss( 'en', '<script>alert("hi")</script>' ) )
				->build() )
		);
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertThatHamcrest(
			$result,
			is( htmlPiece( havingChild( havingTextContents(
				'<script>alert("hi")</script>'
			) ) ) )
		);
	}

}

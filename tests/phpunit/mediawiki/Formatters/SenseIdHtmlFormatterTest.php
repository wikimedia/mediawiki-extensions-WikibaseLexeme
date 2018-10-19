<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use MediaWikiLangTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Title;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lexeme\Domain\DataModel\FormId;
use Wikibase\Lexeme\Domain\DataModel\SenseId;
use Wikibase\Lexeme\Formatters\SenseIdHtmlFormatter;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Formatters\SenseIdHtmlFormatter
 *
 * @license GPL-2.0-or-later
 */
class SenseIdHtmlFormatterTest extends MediaWikiLangTestCase {

	/**
	 * @param FormId $expectedSenseId
	 * @return MockObject|EntityTitleLookup
	 */
	private function getMockTitleLookup( SenseId $expectedSenseId ) {
		$title = $this->getMock( Title::class );
		$title->method( 'isLocal' )->willReturn( true );
		$title->method( 'getLinkUrl' )->willReturn( 'LOCAL-URL#FORM' );

		/** @var EntityTitleLookup|MockObject $titleLookup */
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->method( 'getTitleForId' )
			->with( $expectedSenseId )
			->willReturn( $title );

		return $titleLookup;
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|EntityRevisionLookup
	 */
	private function getMockRevisionLookup() {
		return $this->getMock( EntityRevisionLookup::class );
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|LanguageFallbackIndicator
	 */
	private function getMockLanguageFallbackIndicator() {
		$mock = $this->getMock( LanguageFallbackIndicator::class, [], [], '', false );
		$mock->method( 'getHtml' )
			->willReturn( 'FB-INDICATOR' );
		return $mock;
	}

	private function getLanguageFallbackChain() {
		return new LanguageFallbackChain(
			[
				LanguageWithConversion::factory( 'en' ),
				LanguageWithConversion::factory( 'qqx' ),
			]
		);
	}

	private function getFormatter( $senseId, $lookup ) {
		return new SenseIdHtmlFormatter(
			$this->getMockTitleLookup( $senseId ),
			$lookup,
			new DummyLocalizedTextProvider(),
			$this->getLanguageFallbackChain(),
			$this->getMockLanguageFallbackIndicator()
		);
	}

	public function testFormatId_nonExistingEntity() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMockRevisionLookup();
		$lookup->method( 'getEntityRevision' )
			->willReturn( null );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame( '<a href="LOCAL-URL#FORM">L10-S20</a>', $result );
	}

	public function testFormatId_redirectedEntity() {
		$senseId = new SenseId( 'L10-S20' );
		$exception = $this->createMock( RevisionedUnresolvedRedirectException::class );
		$lookup = $this->getMockRevisionLookup();
		$lookup->method( 'getEntityRevision' )
			->willThrowException( $exception );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame( '<a href="LOCAL-URL#FORM">L10-S20</a>', $result );
	}

	public function testFormatId_oneLemma_noGloss() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMockRevisionLookup();
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withSense( NewSense::havingId( $senseId ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$expected = '<a href="LOCAL-URL#FORM">L10-S20</a>';
		$this->assertSame( $expected, $result );
	}

	public function testFormatId_oneLemma_ownGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMockRevisionLookup();
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'qqx', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		// phpcs:ignore Generic.Files.LineLength
		$expected = '<a href="LOCAL-URL#FORM">(wikibaselexeme-senseidformatter-layout: lemma, gloss)</a>FB-INDICATOR';
		$this->assertSame( $expected, $result );
	}

	public function testFormatId_threeLemmas_ownGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMockRevisionLookup();
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withLemma( 'de', 'Lemma' )
					->withLemma( 'el', 'λεμμα' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'qqx', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		// phpcs:ignore Generic.Files.LineLength
		$expected = '<a href="LOCAL-URL#FORM">(wikibaselexeme-senseidformatter-layout: lemma(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)Lemma(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)λεμμα, gloss)</a>FB-INDICATOR';
		$this->assertSame( $expected, $result );
	}

	public function testFormatId_oneLemma_fallbackGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->getMockRevisionLookup();
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'en', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		// phpcs:ignore Generic.Files.LineLength
		$expected = '<a href="LOCAL-URL#FORM">(wikibaselexeme-senseidformatter-layout: lemma, gloss)</a>FB-INDICATOR';
		$this->assertSame( $expected, $result );
	}

	public function testFormatEntityId_htmlEscapesText() {
		$senseId = new SenseId( 'L999-S666' );
		$lookup = $this->getMockRevisionLookup();
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'en', '<script>alert("hi")</script>' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = $this->getFormatter( $senseId, $lookup );

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame(
			// phpcs:ignore Generic.Files.LineLength
			'<a href="LOCAL-URL#FORM">(wikibaselexeme-senseidformatter-layout: lemma, &lt;script>alert("hi")&lt;/script>)</a>FB-INDICATOR',
			$result
		);
	}

}

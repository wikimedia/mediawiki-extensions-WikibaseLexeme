<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Formatters;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Presentation\Formatters\SenseIdTextFormatter;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers \Wikibase\Lexeme\Presentation\Formatters\SenseIdTextFormatter
 *
 * @license GPL-2.0-or-later
 */
class SenseIdTextFormatterTest extends TestCase {

	private function getEntityIdLabelFormatter(): EntityIdFormatter {
		$formatter = $this->createStub( EntityIdFormatter::class );
		$formatter->method( 'formatEntityId' )
			->willReturnCallback( static function ( EntityId $value ): string {
				return "label of {$value->getSerialization()}";
			} );
		return $formatter;
	}

	public function testFormatId_nonExisting() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->createMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturn( null );
		$formatter = new SenseIdTextFormatter(
			$lookup,
			new DummyLocalizedTextProvider(),
			$this->getEntityIdLabelFormatter()
		);

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame( 'L10-S20', $result );
	}

	public function testFormatId_redirected() {
		$senseId = new SenseId( 'L10-S20' );
		$exception = $this->createMock( RevisionedUnresolvedRedirectException::class );
		$lookup = $this->createMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willThrowException( $exception );
		$formatter = new SenseIdTextFormatter(
			$lookup,
			new DummyLocalizedTextProvider(),
			$this->getEntityIdLabelFormatter()
		);

		$result = $formatter->formatEntityId( $senseId );

		$this->assertSame( 'L10-S20', $result );
	}

	public function testFormatId_oneLemma_ownGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->createMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( static function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withLanguage( 'Q123' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'qqx', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = new SenseIdTextFormatter(
			$lookup,
			new DummyLocalizedTextProvider(),
			$this->getEntityIdLabelFormatter()
		);

		$result = $formatter->formatEntityId( $senseId );

		$expected = '(wikibaselexeme-senseidformatter-layout: lemma, gloss, label of Q123)';
		$this->assertSame( $expected, $result );
	}

	public function testFormatId_threeLemmas_ownGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->createMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( static function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withLemma( 'de', 'Lemma' )
					->withLemma( 'el', 'λεμμα' )
					->withLanguage( 'Q123' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'qqx', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = new SenseIdTextFormatter(
			$lookup,
			new DummyLocalizedTextProvider(),
			$this->getEntityIdLabelFormatter()
		);

		$result = $formatter->formatEntityId( $senseId );

		$expected = '(wikibaselexeme-senseidformatter-layout: ' .
			'lemma' .
			'(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)' .
			'Lemma' .
			'(wikibaselexeme-presentation-lexeme-display-label-separator-multiple-lemma)' .
			'λεμμα' .
			', gloss, label of Q123)';
		$this->assertSame( $expected, $result );
	}

	public function testFormatId_oneLemma_fallbackGlossLanguage() {
		$senseId = new SenseId( 'L10-S20' );
		$lookup = $this->createMock( EntityRevisionLookup::class );
		$lookup->method( 'getEntityRevision' )
			->willReturnCallback( static function ( $entityId ) use ( $senseId ) {
				$entity = NewLexeme::create()
					->withId( $entityId )
					->withLemma( 'en', 'lemma' )
					->withSense( NewSense::havingId( $senseId )
						->withGloss( 'en', 'gloss' ) )
					->build();
				return new EntityRevision( $entity );
			} );
		$formatter = new SenseIdTextFormatter(
			$lookup,
			new DummyLocalizedTextProvider(),
			$this->getEntityIdLabelFormatter()
		);

		$result = $formatter->formatEntityId( $senseId );

		$expected = 'L10-S20';
		$this->assertSame( $expected, $result );
	}

}

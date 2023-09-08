<?php

namespace Wikibase\Lexeme\Tests\Merge;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\Domain\Merge\LexemeSensesMerger;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\Domain\Merge\LexemeSensesMerger
 *
 * @license GPL-2.0-or-later
 */
class LexemeSensesMergerTest extends TestCase {

	/**
	 * @dataProvider provideSamples
	 */
	public function testMerge( Lexeme $expectedTarget, Lexeme $source, Lexeme $target ) {
		$merger = $this->newLexemeSensesMerger();
		$merger->merge( $source, $target );

		$this->assertTrue( $expectedTarget->equals( $target ) );
	}

	public static function provideSamples() {
		yield 'sense gets copied' => [
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)
				->build(),
			self::newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)
				->build(),
			self::newMinimumValidLexeme( 'L2' )->build(),
		];
		yield 'senses gets copied after existing one' => [
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'bar' )
				)->build(),
			self::newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'bar' )
				)->build(),
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)->build(),
		];
		yield 'sense with same gloss gets copied' => [
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' ) )
				->build(),
			self::newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)
				->build(),
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->build(),
		];
		yield 'sense with same gloss and statement gets copied' => [
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
						->withStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L2-S2$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)->build(),
			self::newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L1-S1$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)->build(),
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)->build(),
		];
		yield 'sense with different gloss and statement gets copied' => [
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en-gb', 'colour' )
				)->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
						->withStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L2-S2$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)
				->build(),
			self::newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L1-S1$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)->build(),
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en-gb', 'colour' )
				)->build(),
		];
		yield 'redundant source senses persist' => [
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
				)
				->build(),
			self::newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
				)
				->build(),
			self::newMinimumValidLexeme( 'L2' )->build(),
		];
		yield 'redundant target senses persist' => [
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
				)
				->build(),
			self::newMinimumValidLexeme( 'L1' )
				->build(),
			self::newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
				)
				->build(),
		];
	}

	public function testMergeLeavesTargetIntact() {
		$source = self::newMinimumValidLexeme( 'L1' )->build();
		$target = self::newMinimumValidLexeme( 'L2' )->build();

		$this->newLexemeSensesMerger()->merge( $source, $target );

		$this->assertSame( 'L2', $target->getId()->getSerialization() );
		$this->assertSame( 'Q7', $target->getLanguage()->getSerialization() );
		$this->assertSame( 'Q55', $target->getLexicalCategory()->getSerialization() );
		$this->assertSame( [ 'en' => 'foo' ], $target->getLemmas()->toTextArray() );
	}

	private function newLexemeSensesMerger(): LexemeSensesMerger {
		$guidGenerator = $this->createMock( GuidGenerator::class );
		$guidGenerator->method( 'newGuid' )
			->willReturnCallback( static function ( EntityId $entityId ) {
				return $entityId->getSerialization() . '$00000000-0000-0000-0000-000000000000';
			} );

		return new LexemeSensesMerger(
			$guidGenerator
		);
	}

	/**
	 * @param string $id Lexeme id
	 * @return NewLexeme
	 */
	private static function newMinimumValidLexeme( $id ): NewLexeme {
		return NewLexeme::havingId( $id )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q55' )
			->withLemma( 'en', 'foo' );
	}

}

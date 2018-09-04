<?php

namespace Wikibase\Lexeme\Tests\Merge;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Merge\LexemeSensesMerger;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Merge\TermListMerger;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\Merge\StatementsMerger;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Merge\LexemeSensesMerger
 *
 * @license GPL-2.0-or-later
 */
class LexemeSensesMergerTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @dataProvider provideSamples
	 */
	public function testMerge( Lexeme $expectedTarget, Lexeme $source, Lexeme $target ) {
		$merger = $this->newLexemeSensesMerger();
		$merger->merge( $source, $target );

		$this->assertTrue( $expectedTarget->equals( $target ) );
	}

	public function provideSamples() {
		yield 'sense gets copied' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L2' )->build()
		];
		yield 'senses gets copied after existing one' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'bar' )
				)->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'bar' )
				)->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)->build()
		];
		yield 'sense glosses get merged' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withGloss( 'en-gb', 'colour' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->build()
		];
		yield 'sense statement gets copied' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
						->withStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L2-S1$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)->build(),
			$this->newMinimumValidLexeme( 'L1' )
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
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)->build()
		];
		yield 'sense with statement gets copied' => [
			$this->newMinimumValidLexeme( 'L2' )
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
			$this->newMinimumValidLexeme( 'L1' )
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
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en-gb', 'colour' )
				)->build()
		];
		yield 'redundant source senses persist' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L2' )->build()
		];
		yield 'redundant target senses persist' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withSense(
					NewSense::havingId( 'S1' )
						->withGloss( 'en', 'color' )
				)
				->withSense(
					NewSense::havingId( 'S2' )
						->withGloss( 'en', 'color' )
				)
				->build()
		];
	}

	public function testMergeLeavesTargetIntact() {
		$source = $this->newMinimumValidLexeme( 'L1' )->build();
		$target = $this->newMinimumValidLexeme( 'L2' )->build();

		$merger = $this->newLexemeSensesMerger();
		$merger->merge( $source, $target );

		$this->assertSame( 'L2', $target->getId()->serialize() );
		$this->assertSame( 'Q7', $target->getLanguage()->serialize() );
		$this->assertSame( 'Q55', $target->getLexicalCategory()->serialize() );
		$this->assertSame( [ 'en' => 'foo' ], $target->getLemmas()->toTextArray() );
	}

	private function newLexemeSensesMerger() : LexemeSensesMerger {
		$guidGenerator = $this->createMock( GuidGenerator::class );
		$guidGenerator->method( 'newGuid' )
			->willReturnCallback( function( EntityId $entityId ) {
				return $entityId->getSerialization() . '$00000000-0000-0000-0000-000000000000';
			} );

		$mockProvider = new ChangeOpTestMockProvider( $this );

		$statementChangeOpFactory = new StatementChangeOpFactory(
			$guidGenerator,
			$mockProvider->getMockGuidValidator(),
			WikibaseRepo::getDefaultInstance()->getStatementGuidParser(),
			$mockProvider->getMockSnakValidator(),
			$mockProvider->getMockSnakValidator()
		);

		return new LexemeSensesMerger(
			new StatementsMerger( $statementChangeOpFactory ),
			new TermListMerger()
		);
	}

	/**
	 * @param string $id Lexeme id
	 * @return NewLexeme
	 */
	private function newMinimumValidLexeme( $id ) : NewLexeme {
		return NewLexeme::havingId( $id )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q55' )
			->withLemma( 'en', 'foo' );
	}

}

<?php

namespace Wikibase\Lexeme\Tests\Merge;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Merge\LexemeFormsMerger;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Merge\TermListMerger;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\Merge\StatementsMerger;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Merge\LexemeFormsMerger
 *
 * @license GPL-2.0-or-later
 */
class LexemeFormsMergerTest extends TestCase {

	use PHPUnit4And6Compat;

	/**
	 * @dataProvider provideSamples
	 */
	public function testMerge( Lexeme $expectedTarget, Lexeme $source, Lexeme $target ) {
		$merger = $this->newLexemeFormsMerger();
		$merger->merge( $source, $target );

		$this->assertTrue( $expectedTarget->equals( $target ) );
	}

	public function provideSamples() {
		yield 'form gets copied' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andRepresentation( 'en-gb', 'colour' )
						->andGrammaticalFeature( 'Q2' )
						->andGrammaticalFeature( 'Q1' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andRepresentation( 'en-gb', 'colour' )
						->andGrammaticalFeature( 'Q2' )
						->andGrammaticalFeature( 'Q1' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L2' )->build()
		];
		yield 'forms gets copied after existing one' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andRepresentation( 'en-gb', 'colour' )
						->andGrammaticalFeature( 'Q2' )
						->andGrammaticalFeature( 'Q1' )
				)->withForm(
					NewForm::havingId( 'F2' )
						->andRepresentation( 'en', 'bar' )
						->andGrammaticalFeature( 'Q1' )
				)->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withForm(
					NewForm::havingId( 'F2' )
						->andRepresentation( 'en', 'bar' )
						->andGrammaticalFeature( 'Q1' )
				)->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andRepresentation( 'en-gb', 'colour' )
						->andGrammaticalFeature( 'Q2' )
						->andGrammaticalFeature( 'Q1' )
				)->build()
		];
		yield 'form representations get merged' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andRepresentation( 'en-gb', 'colour' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andRepresentation( 'en-gb', 'colour' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
				)
				->build()
		];
		yield 'forms are considered identical irrespective of grammatical feature order' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andGrammaticalFeature( 'Q2' )
						->andGrammaticalFeature( 'Q1' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andGrammaticalFeature( 'Q1' )
						->andGrammaticalFeature( 'Q2' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andGrammaticalFeature( 'Q2' )
						->andGrammaticalFeature( 'Q1' )
				)
				->build()
		];
		yield 'form statement gets copied' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andGrammaticalFeature( 'Q1' )
						->andStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L2-F1$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andGrammaticalFeature( 'Q1' )
						->andStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L1-F1$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andGrammaticalFeature( 'Q1' )
				)->build()
		];
		yield 'form with statement gets copied' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en-gb', 'colour' )
				)->withForm(
					NewForm::havingId( 'F2' )
						->andRepresentation( 'en', 'color' )
						->andStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L2-F2$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
						->andStatement(
							NewStatement::forProperty( 'P4711' )
								->withGuid( 'L1-F1$00000000-0000-0000-0000-000000000000' )
								->withValue( new LexemeId( 'L42' ) )
								->build()
						)
				)->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en-gb', 'colour' )
				)->build()
		];
		yield 'redundant source forms persist' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
				)
				->withForm(
					NewForm::havingId( 'F2' )
						->andRepresentation( 'en', 'color' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
				)
				->withForm(
					NewForm::havingId( 'F2' )
						->andRepresentation( 'en', 'color' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L2' )->build()
		];
		yield 'redundant target forms persist' => [
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
				)
				->withForm(
					NewForm::havingId( 'F2' )
						->andRepresentation( 'en', 'color' )
				)
				->build(),
			$this->newMinimumValidLexeme( 'L1' )
				->build(),
			$this->newMinimumValidLexeme( 'L2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'color' )
				)
				->withForm(
					NewForm::havingId( 'F2' )
						->andRepresentation( 'en', 'color' )
				)
				->build()
		];
	}

	public function testMergeLeavesTargetIntact() {
		$source = $this->newMinimumValidLexeme( 'L1' )->build();
		$target = $this->newMinimumValidLexeme( 'L2' )->build();

		$merger = $this->newLexemeFormsMerger();
		$merger->merge( $source, $target );

		$this->assertSame( 'L2', $target->getId()->serialize() );
		$this->assertSame( 'Q7', $target->getLanguage()->serialize() );
		$this->assertSame( 'Q55', $target->getLexicalCategory()->serialize() );
		$this->assertSame( [ 'en' => 'foo' ], $target->getLemmas()->toTextArray() );
	}

	private function newLexemeFormsMerger() : LexemeFormsMerger {
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

		return new LexemeFormsMerger(
			new StatementsMerger( $statementChangeOpFactory ),
			new TermListMerger(),
			$guidGenerator
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

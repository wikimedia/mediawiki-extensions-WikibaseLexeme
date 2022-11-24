<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Validators;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\ErisGenerators\ErisTest;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements
 *
 * @license GPL-2.0-or-later
 */
class NoCrossReferencingLexemeStatementsTest extends TestCase {

	use ErisTest;

	private function getLexemeStatementEntityReferenceExtractor() {
		$statementEntityReferenceExtractor = new StatementEntityReferenceExtractor(
			WikibaseRepo::getItemUrlParser()
		);
		return new LexemeStatementEntityReferenceExtractor(
			$statementEntityReferenceExtractor,
			new FormsStatementEntityReferenceExtractor( $statementEntityReferenceExtractor ),
			new SensesStatementEntityReferenceExtractor( $statementEntityReferenceExtractor )
		);
	}

	public function provideValidMerges() {
		$p1l3Snak = new PropertyValueSnak(
			new NumericPropertyId( 'P1' ),
			new EntityIdValue( new LexemeId( 'L3' ) )
		);
		return [
			'Fairly empty lexemes' => [
				NewLexeme::havingId( 'L1' )->withLemma( 'en', 'a' )->build(),
				NewLexeme::havingId( 'L2' )->withLemma( 'de', 'a' )->build(),
			],
			'Lexeme statement snaks referencing other entities' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->withStatement( $p1l3Snak )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->withStatement( $p1l3Snak )
					->build(),
			],
			'Lexeme form snaks referencing other entities' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->withForm( NewForm::havingStatement( $p1l3Snak ) )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->withForm( NewForm::havingStatement( $p1l3Snak ) )
					->build(),
			],
			'Lexeme sense snaks referencing other entities' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->withSense( NewSense::havingStatement( $p1l3Snak ) )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->withSense( NewSense::havingStatement( $p1l3Snak ) )
					->build(),
			],
		];
	}

	/**
	 * @dataProvider provideValidMerges
	 */
	public function testValidMerges( Lexeme $source, Lexeme $target ) {
		$validator = new NoCrossReferencingLexemeStatements(
			$this->getLexemeStatementEntityReferenceExtractor()
		);
		$this->assertTrue( $validator->validate( $source, $target ) );
		$this->assertSame( [], $validator->getViolations() );
	}

	public function provideInvalidMerges() {
		$p1l1Snak = new PropertyValueSnak(
			new NumericPropertyId( 'P1' ),
			new EntityIdValue( new LexemeId( 'L1' ) )
		);
		$p1l2Snak = new PropertyValueSnak(
			new NumericPropertyId( 'P1' ),
			new EntityIdValue( new LexemeId( 'L2' ) )
		);
		return [
			'Lexeme statement snak cross referencing L1 -> L2' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->withStatement( $p1l2Snak )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->build(),
				[
					[ new LexemeId( 'L1' ), new LexemeId( 'L2' ), new LexemeId( 'L2' ) ],
				],
			],
			'Lexeme statement snak cross referencing L2 -> L1' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->withStatement( $p1l1Snak )
					->build(),
				[
					[ new LexemeId( 'L2' ), new LexemeId( 'L1' ), new LexemeId( 'L1' ) ],
				],
			],
			'Lexeme form snak cross referencing L2 -> L1' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->withForm( NewForm::havingStatement( $p1l1Snak )->build() )
					->build(),
				[
					[ new LexemeId( 'L2' ), new LexemeId( 'L1' ), new LexemeId( 'L1' ) ],
				],
			],
			'Lexeme sense snak cross referencing L2 -> L1' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->withSense( NewSense::havingStatement( $p1l1Snak )->build() )
					->build(),
				[
					[ new LexemeId( 'L2' ), new LexemeId( 'L1' ), new LexemeId( 'L1' ) ],
				],
			],
			'Lexeme sense snak cross referencing L2 -> L1 via sense ID' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->withSense( NewSense::havingStatement(
						new PropertyValueSnak(
							new NumericPropertyId( 'P1' ),
							new EntityIdValue( new SenseId( 'L1-S1' ) )
						)
					)->build() )
					->build(),
				[
					[ new LexemeId( 'L2' ), new SenseId( 'L1-S1' ), new LexemeId( 'L1' ) ],
				],
			],
			'Lexeme sense snak cross referencing L2 -> L1 multiple' => [
				NewLexeme::havingId( 'L1' )
					->withLemma( 'en', 'a' )
					->withSense( NewSense::havingStatement( $p1l2Snak )->build() )
					->build(),
				NewLexeme::havingId( 'L2' )
					->withLemma( 'de', 'a' )
					->withSense( NewSense::havingStatement( $p1l1Snak )->build() )
					->build(),
				[
					[ new LexemeId( 'L2' ), new LexemeId( 'L1' ), new LexemeId( 'L1' ) ],
					[ new LexemeId( 'L1' ), new LexemeId( 'L2' ), new LexemeId( 'L2' ) ],
				],
			],
		];
	}

	/**
	 * @dataProvider provideInvalidMerges
	 * @param Lexeme $source
	 * @param Lexeme $target
	 * @param array[] $expectedViolations
	 */
	public function testInvalidMerges( Lexeme $source, Lexeme $target, $expectedViolations ) {
		$validator = new NoCrossReferencingLexemeStatements(
			$this->getLexemeStatementEntityReferenceExtractor()
		);
		$this->assertFalse( $validator->validate( $source, $target ) );
		$this->assertEquals( $expectedViolations, $validator->getViolations() );
	}

}

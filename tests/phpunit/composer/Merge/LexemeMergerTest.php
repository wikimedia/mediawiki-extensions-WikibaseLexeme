<?php

namespace Wikibase\Lexeme\Tests\Merge;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\FormsStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\LexemeStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\SensesStatementEntityReferenceExtractor;
use Wikibase\Lexeme\Domain\Merge\Exceptions\ConflictingLemmaValueException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\CrossReferencingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\DifferentLanguagesException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\DifferentLexicalCategoriesException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\MergingException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\ModificationFailedException;
use Wikibase\Lexeme\Domain\Merge\Exceptions\ReferenceSameLexemeException;
use Wikibase\Lexeme\Domain\Merge\LexemeFormsMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeMerger;
use Wikibase\Lexeme\Domain\Merge\LexemeSensesMerger;
use Wikibase\Lexeme\Domain\Merge\NoCrossReferencingLexemeStatements;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Merge\StatementsMerger;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Lexeme\Domain\Merge\LexemeMerger
 *
 * @license GPL-2.0-or-later
 */
class LexemeMergerTest extends TestCase {

	/**
	 * @var LexemeMerger
	 */
	private $lexemeMerger;

	protected function setUp(): void {
		parent::setUp();

		$this->lexemeMerger = $this->newLexemeMerger();
	}

	public function testLexemesReferenceTheSameObjectCausesException() {
		$lexeme = $this->newMinimumValidLexeme( 'L36' )
			->build();

		$this->expectException( ReferenceSameLexemeException::class );
		$this->lexemeMerger->merge( $lexeme, $lexeme );
	}

	public function testLexemesAreTheSameCausesException() {
		$source = $this->newMinimumValidLexeme( 'L37' )
			->build();
		$target = $source->copy();

		$this->expectException( ReferenceSameLexemeException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testLemmasThatExistBothOnTheTargetAndTheSourceAreKeptOnTheTarget() {
		$source = $this->newLexeme( 'L1' )
			->withLemma( 'en', 'color' )
			->build();
		$target = $this->newLexeme( 'L2' )
			->withLemma( 'en', 'color' )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertSame( 'color', $target->getLemmas()->getByLanguage( 'en' )->getText() );
	}

	public function testLemmasThatExistOnlyOnTheSourceAreAddedToTheTarget() {
		$source = $this->newLexeme( 'L1' )
			->withLemma( 'en', 'color' )
			->build();
		$target = $this->newLexeme( 'L2' )
			->withLemma( 'en-gb', 'colour' )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertSame( 'color', $target->getLemmas()->getByLanguage( 'en' )->getText() );
		$this->assertSame( 'colour', $target->getLemmas()->getByLanguage( 'en-gb' )->getText() );
	}

	/**
	 * @dataProvider provideConflictingLemmas
	 */
	public function testLexemesHaveLemmasWithSameLanguageButDifferentValueCausesException(
		array $sourceLemmas,
		array $targetLemmas
	) {
		$source = $this->newLexeme( 'L1' );
		foreach ( $sourceLemmas as $lemma ) {
			$source = $source->withLemma( $lemma[0], $lemma[1] );
		}
		$source = $source->build();
		$target = $this->newLexeme( 'L2' );
		foreach ( $targetLemmas as $lemma ) {
			$target = $target->withLemma( $lemma[0], $lemma[1] );
		}
		$target = $target->build();

		$this->expectException( ConflictingLemmaValueException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function provideConflictingLemmas() {
		yield [ [ [ 'en', 'bar' ] ], [ [ 'en', 'foo' ] ] ];
		yield [ [ [ 'en', 'bar' ], [ 'en-gb', 'foo' ] ], [ [ 'en-gb', 'foo2' ] ] ];
		yield [ [ [ 'en', 'bar' ] ], [ [ 'en-gb', 'foo2' ], [ 'en', 'baz' ] ] ];
	}

	public function testLexemesHaveDifferentLanguageCausesException() {
		$source = NewLexeme::havingId( 'L1' )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q55' )
			->build();
		$target = NewLexeme::havingId( 'L2' )
			->withLanguage( 'Q8' )
			->withLexicalCategory( 'Q55' )
			->build();

		$this->expectException( DifferentLanguagesException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testLexemesHaveDifferentLexicalCategoriesCausesException() {
		$source = NewLexeme::havingId( 'L1' )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q55' )
			->build();
		$target = NewLexeme::havingId( 'L2' )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q56' )
			->build();

		$this->expectException( DifferentLexicalCategoriesException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenSourceLexemeWithStatementItIsAddedToTarget() {
		$statement = NewStatement::noValueFor( 'P56' )
			->withGuid( 'L1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->build();
		$source->getStatements()->addStatement( $statement );
		$target = $this->newMinimumValidLexeme( 'L2' )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 1, $target->getStatements() );
		$this->assertSame(
			'P56',
			$target->getStatements()->getMainSnaks()[0]->getPropertyId()->serialize()
		);
	}

	public function testGivenSourceLexemeWithStatementReferencingTargetLexemeExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new LexemeId( 'L2' ) )
			->withGuid( 'L1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->build();
		$source->getStatements()->addStatement( $statement );
		$target = $this->newMinimumValidLexeme( 'L2' )
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenSourceWithMultipleRedundantFormsTheyAreIndividuallyAddedToTarget() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en', 'colors' ) )
			->withForm( NewForm::havingId( 'F2' )->andRepresentation( 'en', 'colors' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en-gb', 'colours' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 3, $target->getForms() );
		$f1Representations = $target->getForms()
			->getById( new FormId( 'L2-F1' ) )
			->getRepresentations();
		$this->assertCount( 1, $f1Representations );
		$this->assertSame(
			'colours',
			$f1Representations->getByLanguage( 'en-gb' )->getText()
		);
		$f2Representations = $target->getForms()
			->getById( new FormId( 'L2-F2' ) )
			->getRepresentations();
		$this->assertCount( 1, $f2Representations );
		$this->assertSame(
			'colors',
			$f2Representations->getByLanguage( 'en' )->getText()
		);
		$f3Representations = $target->getForms()
			->getById( new FormId( 'L2-F3' ) )
			->getRepresentations();
		$this->assertCount( 1, $f3Representations );
		$this->assertSame(
			'colors',
			$f3Representations->getByLanguage( 'en' )->getText()
		);
	}

	public function testGivenTargetWithMultipleMatchingFormsAllTheseFormsRemain() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en-gb', 'colours' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en', 'colors' ) )
			->withForm( NewForm::havingId( 'F2' )->andRepresentation( 'en', 'colors' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 3, $target->getForms() );
		$f1Representations = $target->getForms()
			->getById( new FormId( 'L2-F1' ) )
			->getRepresentations();
		$this->assertCount( 1, $f1Representations );
		$this->assertSame(
			'colors',
			$f1Representations->getByLanguage( 'en' )->getText()
		);
		$f2Representations = $target->getForms()
			->getById( new FormId( 'L2-F2' ) )
			->getRepresentations();
		$this->assertCount( 1, $f2Representations );
		$this->assertSame(
			'colors',
			$f2Representations->getByLanguage( 'en' )->getText()
		);
		$f3Representations = $target->getForms()
			->getById( new FormId( 'L2-F3' ) )
			->getRepresentations();
		$this->assertCount( 1, $f3Representations );
		$this->assertSame(
			'colours',
			$f3Representations->getByLanguage( 'en-gb' )->getText()
		);
	}

	public function testGivenLexemesWithMatchingFormsFormRepresentationsAreMerged() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'colors' )
					->andRepresentation( 'en-gb', 'colours' )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en-gb', 'colours' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$forms = $target->getForms();
		$this->assertCount( 1, $forms );
		$representations = $forms->getById( new FormId( 'L2-F1' ) )
			->getRepresentations();
		$this->assertCount( 2, $representations );
		$this->assertSame(
			'colours',
			$representations->getByLanguage( 'en-gb' )->getText()
		);
		$this->assertSame(
			'colors',
			$representations->getByLanguage( 'en' )->getText()
		);
	}

	public function testGivenLexemesWithNonMatchingFormsSourceFormsAreAddedToTarget() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en', 'colors' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( NewForm::havingId( 'F1' )->andRepresentation( 'en-gb', 'colours' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 2, $target->getForms() );
		$f1Representations = $target->getForms()
			->getById( new FormId( 'L2-F1' ) )
			->getRepresentations();
		$this->assertCount( 1, $f1Representations );
		$this->assertSame(
			'colours',
			$f1Representations->getByLanguage( 'en-gb' )->getText()
		);
		$f2Representations = $target->getForms()
			->getById( new FormId( 'L2-F2' ) )
			->getRepresentations();
		$this->assertCount( 1, $f2Representations );
		$this->assertSame(
			'colors',
			$f2Representations->getByLanguage( 'en' )->getText()
		);
	}

	public function testGivenLexemesWithMultipleMatchingFormsFirstMatchMergedRestCopiedUnchanged() {
		$statement = NewStatement::noValueFor( 'P42' )
			->withGuid( 'L1-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andGrammaticalFeature( 'Q47' )
				->andStatement( $statement )
			)->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andGrammaticalFeature( 'Q47' )
			)
			->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en-gb', 'colours' )
					->andGrammaticalFeature( 'Q47' )
			)
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount(
			1,
			$target->getForms()->getById( new FormId( 'L2-F1' ) )->getStatements()
		);
		$this->assertCount(
			0,
			$target->getForms()->getById( new FormId( 'L2-F2' ) )->getStatements()
		);
	}

	public function testGivenSourceLexemeWithFormStatementReferencingTargetLexemeExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new LexemeId( 'L2' ) )
			->withGuid( 'L1-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andStatement( $statement )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenTargetLexemeWithFormStatementReferencingSourceLexemeExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new LexemeId( 'L1' ) )
			->withGuid( 'L2-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andStatement( $statement )
			)
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenSourceLexemeWithFormStatementReferencingTargetsFormExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new FormId( 'L2-F1' ) )
			->withGuid( 'L1-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
					->andStatement( $statement )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'colors' )
			)
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenTargetLexemeWithFormStatementReferencingSourcesFormExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new FormId( 'L1-F1' ) )
			->withGuid( 'L2-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en-gb', 'colours' )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'colors' )
					->andStatement( $statement )
			)
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	/**
	 * @dataProvider provideFormMatchingSamples
	 */
	public function testFormMatchesAreDetected(
		$matchingOrNot,
		NewForm $sourceForm,
		NewForm $targetForm
	) {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm( $sourceForm )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm( $targetForm )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( $matchingOrNot ? 1 : 2, $target->getForms() );
	}

	public function provideFormMatchingSamples() {
		yield 'identical representations cause match' => [
			true,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' )
		];
		yield 'identical representations and underspecified grammatical features cause no match' => [
			false,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' )
				->andGrammaticalFeature( 'Q1' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' )
		];
		yield 'different representations prevent match' => [
			false,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'colors' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colours' )
		];
		yield 'one identical and no contradicting representation causes match' => [
			true,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'colors' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'colors' )
				->andRepresentation( 'en-gb', 'colours' )
		];
		yield 'contradicting representations prevent match' => [
			false,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'colors' )
		];
		yield 'identical representations and grammatical features cause match' => [
			true,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' )
		];
		yield 'different grammatical features prevent match' => [
			false,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' )
				->andGrammaticalFeature( 'Q3' )
		];
		yield 'order of parts in identifier is irrelevant' => [
			true,
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en', 'color' )
				->andRepresentation( 'en-gb', 'colour' )
				->andGrammaticalFeature( 'Q2' )
				->andGrammaticalFeature( 'Q1' ),
			NewForm::havingId( 'F1' )
				->andRepresentation( 'en-gb', 'colour' )
				->andRepresentation( 'en', 'color' )
				->andGrammaticalFeature( 'Q1' )
				->andGrammaticalFeature( 'Q2' )
		];
	}

	public function testGivenSourceLexemeWithFormWithStatementItIsAddedToMatchingTargetForm() {
		$statement = NewStatement::noValueFor( 'P56' )
			->withGuid( 'L1-F1$6fbf3e32-aa9a-418e-9fea-665f9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'color' )
					->andStatement( $statement )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withForm(
				NewForm::havingId( 'F1' )->andRepresentation( 'en', 'color' )
			)
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 1, $target->getForms() );
		$f1Statements = $target->getForms()->getById( new FormId( 'L2-F1' ) )->getStatements();
		$this->assertCount( 1, $f1Statements );
		foreach ( $f1Statements as $f1Statement ) {
			$this->assertStringStartsWith( 'L2-F1$', $f1Statement->getGuid() );
			$this->assertSame( 'P56', $f1Statement->getPropertyId()->serialize() );
		}
	}

	public function testGivenMergingExceptionWhileMergingForms_exceptionBubblesUp() {
		$expectedException = $this->createMock( MergingException::class );
		$throwingFormsMerger = $this->createMock( LexemeFormsMerger::class );
		$throwingFormsMerger->expects( $this->once() )
			->method( 'merge' )
			->willThrowException( $expectedException );
		$sensesMerger = new LexemeSensesMerger(
			new GuidGenerator()
		);

		$merger = new LexemeMerger(
			$this->createMock( StatementsMerger::class ),
			$throwingFormsMerger,
			$sensesMerger,
			$this->newValidNoCrossReferencingLexemeStatementsValidator()
		);

		try {
			$merger->merge(
				$this->newMinimumValidLexeme( 'L1' )->build(),
				$this->newMinimumValidLexeme( 'L2' )->build()
			);
			$this->fail( 'Expected exception did not happen' );
		} catch ( Exception $e ) {
			$this->assertSame(
				$expectedException,
				$e
			);
		}
	}

	public function testGivenOtherException_exceptionIsConvertedToModificationFailedException() {
		$expectedException = $this->createMock( ChangeOpException::class );
		$throwingStatementsMerger = $this->createMock( StatementsMerger::class );
		$throwingStatementsMerger->expects( $this->once() )
			->method( 'merge' )
			->willThrowException( $expectedException );

		$merger = new LexemeMerger(
			$throwingStatementsMerger,
			$this->createMock( LexemeFormsMerger::class ),
			$this->createMock( LexemeSensesMerger::class ),
			$this->newValidNoCrossReferencingLexemeStatementsValidator()
		);

		try {
			$merger->merge(
				$this->newMinimumValidLexeme( 'L1' )->build(),
				$this->newMinimumValidLexeme( 'L2' )->build()
			);
			$this->fail( 'Expected exception did not happen' );
		} catch ( ModificationFailedException $e ) {
			$this->assertSame(
				$expectedException,
				$e->getPrevious()
			);
		} catch ( Exception $e ) {
			$this->fail( 'unexpected exception type thrown' );
		}
	}

	public function testGivenSourceWithMultipleRedundantSensesTheyAreIndividuallyAddedToTarget() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withSense( NewSense::havingId( 'S1' )->withGloss( 'en', 'colors' ) )
			->withSense( NewSense::havingId( 'S2' )->withGloss( 'en', 'colors' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withSense( NewSense::havingId( 'S1' )->withGloss( 'en-gb', 'colours' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 3, $target->getSenses() );
		$s1Glosses = $target->getSenses()
			->getById( new SenseId( 'L2-S1' ) )
			->getGlosses();
		$this->assertCount( 1, $s1Glosses );
		$this->assertSame(
			'colours',
			$s1Glosses->getByLanguage( 'en-gb' )->getText()
		);
		$s2Glosses = $target->getSenses()
			->getById( new SenseId( 'L2-S2' ) )
			->getGlosses();
		$this->assertCount( 1, $s2Glosses );
		$this->assertSame(
			'colors',
			$s2Glosses->getByLanguage( 'en' )->getText()
		);
		$s3Glosses = $target->getSenses()
			->getById( new SenseId( 'L2-S3' ) )
			->getGlosses();
		$this->assertCount( 1, $s3Glosses );
		$this->assertSame(
			'colors',
			$s3Glosses->getByLanguage( 'en' )->getText()
		);
	}

	public function testGivenTargetWithMultipleMatchingSensesAllTheseSensesRemain() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withSense( NewSense::havingId( 'S1' )->withGloss( 'en-gb', 'colours' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withSense( NewSense::havingId( 'S1' )->withGloss( 'en', 'colors' ) )
			->withSense( NewSense::havingId( 'S2' )->withGloss( 'en', 'colors' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 3, $target->getSenses() );
		$s1Glosses = $target->getSenses()
			->getById( new SenseId( 'L2-S1' ) )
			->getGlosses();
		$this->assertCount( 1, $s1Glosses );
		$this->assertSame(
			'colors',
			$s1Glosses->getByLanguage( 'en' )->getText()
		);
		$s2Glosses = $target->getSenses()
			->getById( new SenseId( 'L2-S2' ) )
			->getGlosses();
		$this->assertCount( 1, $s2Glosses );
		$this->assertSame(
			'colors',
			$s2Glosses->getByLanguage( 'en' )->getText()
		);
		$s3Glosses = $target->getSenses()
			->getById( new SenseId( 'L2-S3' ) )
			->getGlosses();
		$this->assertCount( 1, $s3Glosses );
		$this->assertSame(
			'colours',
			$s3Glosses->getByLanguage( 'en-gb' )->getText()
		);
	}

	public function testGivenLexemesWithNonMatchingSensesSourceSensesAreAddedToTarget() {
		$source = $this->newMinimumValidLexeme( 'L1' )
			->withSense( NewSense::havingId( 'S1' )->withGloss( 'en', 'colors' ) )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withSense( NewSense::havingId( 'S1' )->withGloss( 'en-gb', 'colours' ) )
			->build();

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 2, $target->getSenses() );
		$s1Glosses = $target->getSenses()
			->getById( new SenseId( 'L2-S1' ) )
			->getGlosses();
		$this->assertCount( 1, $s1Glosses );
		$this->assertSame(
			'colours',
			$s1Glosses->getByLanguage( 'en-gb' )->getText()
		);
		$s2Glosses = $target->getSenses()
			->getById( new SenseId( 'L2-S2' ) )
			->getGlosses();
		$this->assertCount( 1, $s2Glosses );
		$this->assertSame(
			'colors',
			$s2Glosses->getByLanguage( 'en' )->getText()
		);
	}

	public function testGivenSourceLexemeWithSenseStatementReferencingTargetLexemeExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new LexemeId( 'L2' ) )
			->withGuid( 'L1-S1$6fbs3e32-aa9a-418e-9fea-665s9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en-gb', 'colours' )
					->withStatement( $statement )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenTargetLexemeWithSenseStatementReferencingSourceLexemeExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new LexemeId( 'L1' ) )
			->withGuid( 'L2-S1$6fbs3e32-aa9a-418e-9fea-665s9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en-gb', 'colours' )
					->withStatement( $statement )
			)
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenSourceLexemeWithSenseStatementReferencingTargetsSenseExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new SenseId( 'L2-S1' ) )
			->withGuid( 'L1-S1$6fbs3e32-aa9a-418e-9fea-665s9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en-gb', 'colours' )
					->withStatement( $statement )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en', 'colors' )
			)
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenTargetLexemeWithSenseStatementReferencingSourcesSenseExceptionIsThrown() {
		$statement = NewStatement::forProperty( 'P42' )
			->withValue( new SenseId( 'L1-S1' ) )
			->withGuid( 'L2-S1$6fbs3e32-aa9a-418e-9fea-665s9fee0e56' )
			->build();

		$source = $this->newMinimumValidLexeme( 'L1' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en-gb', 'colours' )
			)
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en', 'colors' )
					->withStatement( $statement )
			)
			->build();

		$this->expectException( CrossReferencingException::class );
		$this->lexemeMerger->merge( $source, $target );
	}

	public function testGivenMergingExceptionWhileMergingSenses_exceptionBubblesUp() {
		$expectedException = $this->createMock( MergingException::class );
		$formMerger = $this->createMock( LexemeFormsMerger::class );
		$throwingSensesMerger = $this->createMock( LexemeSensesMerger::class );
		$throwingSensesMerger->expects( $this->once() )
			->method( 'merge' )
			->willThrowException( $expectedException );

		$merger = new LexemeMerger(
			$this->createMock( StatementsMerger::class ),
			$formMerger,
			$throwingSensesMerger,
			$this->newValidNoCrossReferencingLexemeStatementsValidator()
		);

		try {
			$merger->merge(
				$this->newMinimumValidLexeme( 'L1' )->build(),
				$this->newMinimumValidLexeme( 'L2' )->build()
			);
			$this->fail( 'Expected exception did not happen' );
		} catch ( Exception $e ) {
			$this->assertSame(
				$expectedException,
				$e
			);
		}
	}

	public function testGivenSelfReferentialLexemesMergeIsAllowed() {
		$sourceStatement = NewStatement::forProperty( 'P1' )
			->withValue( new LexemeId( 'L1' ) )
			->withGuid( 'L1$8af6a76b-298f-473a-ae77-24c065cf6e8f' )
			->build();
		$source = $this->newMinimumValidLexeme( 'L1' )->build();
		$source->getStatements()->addStatement( $sourceStatement );
		$targetStatement = NewStatement::forProperty( 'P2' )
			->withValue( new LexemeId( 'L2' ) )
			->withGuid( 'L2$8b59398e-da52-4d53-8cc4-39fb0cc2b4af' )
			->build();
		$target = $this->newMinimumValidLexeme( 'L2' )->build();
		$target->getStatements()->addStatement( $targetStatement );

		$this->lexemeMerger->merge( $source, $target );

		$this->assertCount( 2, $target->getStatements() );
	}

	/**
	 * Use this method if you want to manually add lemmas later
	 *
	 * @param string $id Lexeme id
	 * @return NewLexeme Add lemmas to avoid randomness and possible collisions
	 */
	private function newLexeme( $id ): NewLexeme {
		return NewLexeme::havingId( $id )
			->withLanguage( 'Q7' )
			->withLexicalCategory( 'Q55' );
	}

	/**
	 * Use this method if you do not plan to manually add lemmas later
	 *
	 * @param string $id Lexeme id
	 * @return NewLexeme With a stable lemma so randomness and possible collisions are avoided
	 */
	private function newMinimumValidLexeme( $id ): NewLexeme {
		return $this->newLexeme( $id )
			->withLemma( 'en', 'mergeme' );
	}

	private function newLexemeMerger(): LexemeMerger {
		$statementsMerger = WikibaseRepo::getChangeOpFactoryProvider()
			->getMergeFactory()
			->getStatementsMerger();

		// Tests depend on correct behaviour, so don't mock NoCrossReferencingLexemeStatements
		$baseExtractor = new StatementEntityReferenceExtractor( WikibaseRepo::getItemUrlParser() );
		$noCrossReferencingStatementsValidator = new NoCrossReferencingLexemeStatements(
			new LexemeStatementEntityReferenceExtractor(
				$baseExtractor,
				new FormsStatementEntityReferenceExtractor( $baseExtractor ),
				new SensesStatementEntityReferenceExtractor( $baseExtractor )
			)
		);

		return new LexemeMerger(
			$statementsMerger,
			new LexemeFormsMerger(
				$statementsMerger,
				new GuidGenerator()
			),
			new LexemeSensesMerger(
				new GuidGenerator()
			),
			$noCrossReferencingStatementsValidator
		);
	}

	private function newValidNoCrossReferencingLexemeStatementsValidator(): NoCrossReferencingLexemeStatements {
		$crossRefValidator = $this->createMock( NoCrossReferencingLexemeStatements::class );
		$crossRefValidator->method( 'validate' )
			->with( $this->isInstanceOf( Lexeme::class ), $this->isInstanceOf( Lexeme::class ) )
			->willReturn( true );
		return $crossRefValidator;
	}

}

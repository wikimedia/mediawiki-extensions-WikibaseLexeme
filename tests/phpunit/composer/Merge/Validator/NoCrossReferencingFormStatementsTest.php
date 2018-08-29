<?php

namespace Wikibase\Lexeme\Tests\Merge\Validator;

use Eris\Generator;
use Wikibase\Lexeme\Tests\ErisGenerators\ErisTest;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\Merge\Validator\NoCrossReferencingFormStatements;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Merge\Validator\NoCrossReferencingStatements;

/**
 * @covers \Wikibase\Lexeme\Merge\Validator\NoCrossReferencingFormStatements
 *
 * @license GPL-2.0-or-later
 */
class NoCrossReferencingFormStatementsTest extends TestCase {

	use ErisTest;
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider provideSamplesWithIncompleteForms
	 */
	public function testCombinationsWithIncompleteForms(
		$verdict,
		array $upstreamVerdicts,
		Lexeme $source,
		Lexeme $target
	) {
		$this->assertVerdictsAreCombinedCorrectly( $verdict, $upstreamVerdicts, $source, $target );
	}

	public function assertVerdictsAreCombinedCorrectly(
		$verdict,
		array $upstreamVerdicts,
		Lexeme $source,
		Lexeme $target
	) {
		$upstreamValidator = $this->createMock( NoCrossReferencingStatements::class );
		$upstreamValidator
			->expects( $this->exactly( count( $upstreamVerdicts ) ) )
			->method( 'validate' )
			->willReturnOnConsecutiveCalls( ...$upstreamVerdicts );

		$validator = new NoCrossReferencingFormStatements( $upstreamValidator );

		$this->assertSame( $verdict, $validator->validate( $source, $target ) );
	}

	public function provideSamplesWithIncompleteForms() {
		yield 'no forms, no cross referencing at form level' => [
			true,
			[],
			NewLexeme::create()->build(),
			NewLexeme::create()->build()
		];
		yield 'source form supposedly referencing target' => [
			false,
			[ false ],
			NewLexeme::create()
				->withForm(
					NewForm::any()
				)
				->build(),
			NewLexeme::create()->build()
		];
		yield 'source supposedly referencing target form' => [
			false,
			[ false ],
			NewLexeme::create()->build(),
			NewLexeme::create()
				->withForm(
					NewForm::any()
				)
				->build()
		];
	}

	public function testCombinationsWithSourceAndTargetForms() {
		$this->eris()->forAll(
			new Generator\BooleanGenerator(),
			new Generator\BooleanGenerator(),
			new Generator\BooleanGenerator()
		)
			->then( function ( $sourceFormTargetForm, $sourceFormTarget, $sourceTargetForm ) {
				$verdicts = [ $sourceFormTargetForm, $sourceFormTarget, $sourceTargetForm ];
				$this->assertVerdictsAreCombinedCorrectly(
					!in_array( false, $verdicts ),
					$verdicts,
					NewLexeme::create()
						->withForm(
							NewForm::any()
						)
						->build(),
					NewLexeme::create()
						->withForm(
							NewForm::any()
						)
						->build()
				);
			} );
	}

}

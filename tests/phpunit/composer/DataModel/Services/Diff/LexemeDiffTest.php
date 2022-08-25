<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\Domain\Diff\ChangeFormDiffOp;
use Wikibase\Lexeme\Domain\Diff\ChangeSenseDiffOp;
use Wikibase\Lexeme\Domain\Diff\LexemeDiff;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @covers \Wikibase\Lexeme\Domain\Diff\LexemeDiff
 *
 * @license GPL-2.0-or-later
 */
class LexemeDiffTest extends TestCase {

	/**
	 * @dataProvider provideIsEmpty
	 */
	public function testIsEmpty( $expected, LexemeDiff $lexemeDiff ) {
		$this->assertSame( $expected, $lexemeDiff->isEmpty() );
	}

	public function provideIsEmpty() {
		$f1 = new FormId( 'L1-F1' );
		$s1 = new SenseId( 'L1-S1' );
		$addAOp = new DiffOpAdd( 'a' );
		$addQ1Op = new DiffOpAdd( new ItemId( 'Q1' ) );
		$changeQ2Q3Op = new DiffOpChange( new ItemId( 'Q2' ), new ItemId( 'Q3' ) );
		$addP1Op = new DiffOpAdd( NewStatement::noValueFor( 'P1' )->build() );
		$change12Op = new DiffOpChange( 1, 2 );

		yield 'Empty diff is empty' => [ true, new LexemeDiff() ];
		yield 'lemmas change' => [
			false,
			new LexemeDiff( [ 'lemmas' => new Diff( [ $addAOp ] ) ] )
		];
		yield 'lexical category changes' => [
			false,
			new LexemeDiff( [ 'lexicalCategory' => new Diff( [ $addQ1Op ] ) ] )
		];
		yield 'language changes' => [
			false,
			new LexemeDiff( [ 'language' => new Diff( [ $changeQ2Q3Op ] ) ] )
		];
		yield 'claims change' => [
			false,
			new LexemeDiff( [ 'claim' => new Diff( [ $addP1Op ] ) ] )
		];
		yield 'form grammatical features change' => [
			false,
			new LexemeDiff( [ 'forms' =>
				new ChangeFormDiffOp( $f1, new Diff( [ 'grammaticalFeatures' => $addAOp ], true ) )
			] ) ];
		yield 'sense glosses change' => [
			false,
			new LexemeDiff( [ 'senses' =>
				new ChangeSenseDiffOp( $s1, new Diff( [ 'glosses' => new Diff( [ $addAOp ] ) ] ) )
			] )
		];
		yield 'next form ID changes' => [
			false,
			new LexemeDiff( [ 'nextFormId' => new Diff( [ $change12Op ] ) ] )
		];
		yield 'next sense ID changes' => [
			false,
			new LexemeDiff( [ 'nextSenseId' => new Diff( [ $change12Op ] ) ] )
		];
	}

}

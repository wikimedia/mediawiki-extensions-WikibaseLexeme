<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Services\Diff\ChangeFormDiffOp;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiff;

/**
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiff
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
		$addAOp = new DiffOpAdd( 'a' );

		yield 'Empty diff is empty' => [ true, new LexemeDiff() ];
		yield 'form grammatical feature change' => [
			false,
			new LexemeDiff( [ 'forms' =>
				new ChangeFormDiffOp( $f1, new Diff( [ 'grammaticalFeatures' => $addAOp ], true ) )
			] ) ];
	}

}

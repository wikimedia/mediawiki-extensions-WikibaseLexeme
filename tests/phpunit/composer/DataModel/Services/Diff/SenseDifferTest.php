<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DataModel\Services\Diff\SenseDiffer;
use Wikibase\Lexeme\Tests\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\AddSenseDiff
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\ChangeSenseDiffOp
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\SenseDiffer
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\RemoveSenseDiff
 *
 * @license GPL-2.0-or-later
 */
class SenseDifferTest extends TestCase {

	/**
	 * @dataProvider provideDiffEntities
	 */
	public function testDiffEntities_usingEmpty( $sense1, $sense2, $expectedEmpty ) {
		$differ = new SenseDiffer();
		$entityDiff = $differ->diffEntities( $sense1, $sense2 );
		$this->assertEquals( $expectedEmpty, $entityDiff->isEmpty() );
	}

	public function provideDiffEntities() {
		$newS1 = NewSense::havingId( 'S1' );

		$newS1WithEnFooGloss = $newS1->withGloss( 'en', 'Foo' );
		$s1WithEnFooGloss = $newS1WithEnFooGloss->build();
		$s1WithEnBarGloss = $newS1->withGloss( 'en', 'Bar' )->build();
		$s1WithDeFooGloss = $newS1->withGloss( 'de', 'Foo' )->build();

		yield 'General: 2 senses with the same gloss, no diff' =>
		[ $s1WithEnFooGloss, $s1WithEnFooGloss, true ];
		yield 'General: 2 senses with differing gloss language, some diff' =>
		[ $s1WithEnFooGloss, $s1WithDeFooGloss, false ];
		yield 'General: 2 senses with differing gloss text, some diff' =>
		[ $s1WithEnFooGloss, $s1WithEnBarGloss, false ];

		// TODO add tests for statement changes
	}

}

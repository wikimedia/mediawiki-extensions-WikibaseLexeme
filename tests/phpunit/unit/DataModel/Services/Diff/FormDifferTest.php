<?php

namespace Wikibase\Lexeme\Tests\Unit\DataModel\Services\Diff;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Diff\FormDiffer;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;

/**
 * @covers \Wikibase\Lexeme\Domain\Diff\AddFormDiff
 * @covers \Wikibase\Lexeme\Domain\Diff\ChangeFormDiffOp
 * @covers \Wikibase\Lexeme\Domain\Diff\FormDiffer
 * @covers \Wikibase\Lexeme\Domain\Diff\RemoveFormDiff
 *
 * @license GPL-2.0-or-later
 */
class FormDifferTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideDiffEntities
	 */
	public function testDiffEntities_usingEmpty( $form1, $form2, $expectedEmpty ) {
		$differ = new FormDiffer();
		$entityDiff = $differ->diffEntities( $form1, $form2 );
		$this->assertEquals( $expectedEmpty, $entityDiff->isEmpty() );
	}

	public function provideDiffEntities() {
		$newF1 = NewForm::havingId( 'F1' );
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$newF1WithEnFooRep = $newF1->andRepresentation( 'en', 'Foo' );
		$f1WithEnFooRep = $newF1WithEnFooRep->build();
		$f1WithEnBarRep = $newF1->andRepresentation( 'en', 'Bar' )->build();
		$f1WithDeFooRep = $newF1->andRepresentation( 'de', 'Foo' )->build();

		yield 'General: 2 forms with the same representation, no diff' =>
		[ $f1WithEnFooRep, $f1WithEnFooRep, true ];
		yield 'General: 2 forms with differing representation language, some diff' =>
		[ $f1WithEnFooRep, $f1WithDeFooRep, false ];
		yield 'General: 2 forms with differing representation text, some diff' =>
		[ $f1WithEnFooRep, $f1WithEnBarRep, false ];

		$f1WithQ1GF = $newF1WithEnFooRep->andGrammaticalFeature( $q1 )->build();
		$f1WithQ2GF = $newF1WithEnFooRep->andGrammaticalFeature( $q2 )->build();

		yield 'Grammatical Features: 2 forms with the same grammatical feature, no diff' =>
		[ $f1WithQ1GF, $f1WithQ1GF, true ];
		yield 'Grammatical Features: 2 forms with differing grammatical features, some diff' =>
		[ $f1WithQ1GF, $f1WithQ2GF, false ];
		yield 'Grammatical Features: adding a grammatical feature, some diff' =>
		[ $f1WithEnFooRep, $f1WithQ1GF, false ];
		yield 'Grammatical Features: removing a grammatical feature, some diff' =>
		[ $f1WithQ1GF, $f1WithEnFooRep, false ];

		// TODO add tests for statement changes
	}

}

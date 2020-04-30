<?php

namespace Wikibase\Lexeme\Tests\Unit\Merge\Validator;

use MediaWikiUnitTestCase;
use Wikibase\Lexeme\Domain\Merge\Validator\FormMergeability;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;

/**
 * @covers \Wikibase\Lexeme\Domain\Merge\Validator\FormMergeability
 *
 * @license GPL-2.0-or-later
 */
class FormMergeabilityTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideMatchingSamples
	 */
	public function testPositiveMatch( Form $source, Form $target ) {
		$matcher = new FormMergeability();
		$this->assertTrue( $matcher->validate( $source, $target ) );
	}

	public function provideMatchingSamples() {
		yield 'identical representations' => [
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->build(),
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->build()
		];
		yield 'a common representation' => [
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->build(),
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->andRepresentation( 'de-sh', 'Keppn' )
				->build()
		];
		yield 'identical representation and grammatical feature' => [
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->andGrammaticalFeature( 'Q7' )
				->build(),
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->andGrammaticalFeature( 'Q7' )
				->build()
		];
		yield 'irrespective of grammatical feature order' => [
			NewForm::any()
				->andRepresentation( 'en', 'foo' )
				->andGrammaticalFeature( 'Q7' )
				->andGrammaticalFeature( 'Q9' )
				->build(),
			NewForm::any()
				->andRepresentation( 'en', 'foo' )
				->andGrammaticalFeature( 'Q9' )
				->andGrammaticalFeature( 'Q7' )
				->build()
		];
	}

	/**
	 * @dataProvider provideFailingSamples
	 */
	public function testNegativeMatch( Form $source, Form $target ) {
		$matcher = new FormMergeability();
		$this->assertFalse( $matcher->validate( $source, $target ) );
	}

	public function provideFailingSamples() {
		yield 'different representations in the same spelling variant' => [
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->build(),
			NewForm::any()
				->andRepresentation( 'de', 'Kapitaen' )
				->build()
		];
		yield 'different grammatical features' => [
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->andGrammaticalFeature( 'Q7' )
				->build(),
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->build()
		];
		yield 'no common representations' => [
			NewForm::any()
				->andRepresentation( 'de', 'Kapitän' )
				->build(),
			NewForm::any()
				->andRepresentation( 'en', 'Captain' )
				->build()
		];
	}

}

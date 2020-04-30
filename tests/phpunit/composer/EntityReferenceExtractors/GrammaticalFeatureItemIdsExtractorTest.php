<?php

namespace Wikibase\Lexeme\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lexeme\Domain\EntityReferenceExtractors\GrammaticalFeatureItemIdsExtractor;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\Domain\EntityReferenceExtractors\GrammaticalFeatureItemIdsExtractor
 *
 * @license GPL-2.0-or-later
 */
class GrammaticalFeatureItemIdsExtractorTest extends TestCase {

	/**
	 * @dataProvider lexemeWithFormsProvider
	 */
	public function testGivenLexemeWithForms_returnsMergedGrammaticalFeatures(
		Lexeme $lexeme,
		$expected
	) {
		$extractor = new GrammaticalFeatureItemIdsExtractor();
		$this->assertEquals(
			$expected,
			$extractor->extractEntityIds( $lexeme )
		);
	}

	/**
	 * @dataProvider nonLexemeProvider
	 */
	public function testGivenNotALexeme_throwsException( $nonLexeme ) {
		$extractor = new GrammaticalFeatureItemIdsExtractor();
		$this->expectException( InvalidArgumentException::class );
		$extractor->extractEntityIds( $nonLexeme );
	}

	public function nonLexemeProvider() {
		return [
			[ new Item() ],
			[ new Property( null, null, 'string' ) ],
		];
	}

	public function lexemeWithFormsProvider() {
		return [
			'no forms' => [ NewLexeme::havingId( 'L321' )->build(), [] ],
			'one form with multiple grammatical features' => [
				NewLexeme::havingId( 'L123' )
					->withForm( NewForm::havingId( 'F1' )
						->andGrammaticalFeature( 'Q2' )
						->andGrammaticalFeature( 'Q3' ) )
					->build(),
				[ new ItemId( 'Q2' ), new ItemId( 'Q3' ), ]
			],
			'multiple forms with recurring grammatical features' => [
				NewLexeme::havingId( 'L234' )
					->withForm( NewForm::havingId( 'F1' ) )
					->withForm( NewForm::havingId( 'F2' )
						->andGrammaticalFeature( 'Q321' )
						->andGrammaticalFeature( 'Q123' ) )
					->withForm( NewForm::havingId( 'F3' )
						->andGrammaticalFeature( 'Q234' )
						->andGrammaticalFeature( 'Q123' ) )
					->build(),
				[ new ItemId( 'Q123' ), new ItemId( 'Q321' ), new ItemId( 'Q234' ) ]
			],
		];
	}

}

<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Repo\Search\Fields\WikibaseIndexField;

/**
 * Lemma field test.
 */
abstract class LexemeFieldTest extends TestCase {
	const CATEGORY_ID = 'Q456';
	const LANGUAGE_ID = 'Q123';

	/**
	 * @return array
	 */
	abstract protected function getTestData();

	/**
	 * @param WikibaseIndexField $field
	 * @param mixed $expected
	 * @dataProvider getTestData
	 */
	public function testLemmaField( $field, $expected ) {

		$form1 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'Color' )
			->andRepresentation( 'en-gb', 'colour' )
			->andGrammaticalFeature( 'Q111' )
			->andGrammaticalFeature( 'Q222' );
		$form2 = NewForm::havingId( 'F2' )
			->andRepresentation( 'de', 'testform' )
			->andRepresentation( 'de-ch', 'Test Form' );

		$lexeme = NewLexeme::create()
			->withId( 'L1' )
			->withLanguage( self::LANGUAGE_ID )
			->withLemma( 'en', 'Test Lemma' )
			->withForm( $form1 )
			->withForm( $form2 )
			->withLexicalCategory( self::CATEGORY_ID )
			->build();

		$data = $field->getFieldData( $lexeme );
		$this->assertEquals( $expected, $data );
	}

}

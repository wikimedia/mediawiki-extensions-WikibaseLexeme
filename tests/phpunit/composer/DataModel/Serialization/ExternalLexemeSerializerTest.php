<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use PHPUnit_Framework_TestCase;
use Serializers\Exceptions\SerializationException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Serialization\ExternalLexemeSerializer;
use Wikibase\Lexeme\DataModel\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\DataModel\Serialization\LexemeSerializer
 * @covers \Wikibase\Lexeme\DataModel\Serialization\FormSerializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class ExternalLexemeSerializerTest extends PHPUnit_Framework_TestCase {

	private function newSerializer() {
		$statementListSerializer = $this->getMockBuilder( StatementListSerializer::class )
			->disableOriginalConstructor()
			->getMock();
		$statementListSerializer->method( 'serialize' )
			->will( $this->returnCallback( function ( StatementList $statementList ) {
				return implode( '|', $statementList->getPropertyIds() );
			} ) );

		$termListSerializer = $this->getMockBuilder( TermListSerializer::class )
			->disableOriginalConstructor()
			->getMock();
		$termListSerializer->method( 'serialize' )
			->will( $this->returnCallback( function ( TermList $termList ) {
				return $termList->toTextArray();
			} ) );

		return new ExternalLexemeSerializer(
			new StorageLexemeSerializer( $termListSerializer, $statementListSerializer )
		);
	}

	public function testIsSerializerFor() {
		$serializer = $this->newSerializer();

		$this->assertTrue( $serializer->isSerializerFor( new Lexeme() ) );
	}

	public function provideInvalidObjects() {
		return [
			[ null ],
			[ '' ],
			[ [] ],
			[ new Item() ]
		];
	}

	/**
	 * @dataProvider provideInvalidObjects
	 */
	public function testSerializeException( $object ) {
		$serializer = $this->newSerializer();

		$this->setExpectedException( SerializationException::class );
		$serializer->serialize( $object );
	}

	/**
	 * @dataProvider provideInvalidObjects
	 */
	public function testIsNotSerializerFor( $object ) {
		$serializer = $this->newSerializer();

		$this->assertFalse( $serializer->isSerializerFor( $object ) );
	}

	public function testEmptyLexeme_SerializationHasType() {
		$lexeme = NewLexeme::create()->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'type', 'lexeme' ) );
	}

	public function testLexemeWithLexicalCategory_SerializesLexicalCategory() {
		$lexeme = NewLexeme::create()
			->withLexicalCategory( 'Q1' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'lexicalCategory', 'Q1' ) );
	}

	public function testLexemeWithLanguage_SerializesLanguage() {
		$lexeme = NewLexeme::create()
			->withLanguage( 'Q2' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'language', 'Q2' ) );
	}

	public function testLexemeWithId_SerializesId() {
		$lexeme = NewLexeme::create()
			->withId( 'L1' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'id', 'L1' ) );
	}

	public function testLexemeWithStatements_SerializesStatements() {
		$lexeme = NewLexeme::create()
			->withStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'claims', 'P1' ) );
	}

	public function testLexemeWithLemmas_SerializesLemmas() {
		$lexeme = NewLexeme::create()
			->withLemma( 'ja', 'Tokyo' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'lemmas', hasKeyValuePair( 'ja', 'Tokyo' ) ) );
	}

	public function testLexemeWithoutForms_LexemeSerializationEmptyArrayAsForms() {
		$lexeme = NewLexeme::create()->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'forms', emptyArray() ) );
	}

	public function testLexemeHasFormWithId_LexemeSerializationHasFormWithThatId() {
		$lexeme = NewLexeme::havingForm(
			NewForm::havingId( 'F1' )
		)->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat(
			$serialization,
			hasKeyValuePair( 'forms', hasItemInArray( hasKeyValuePair( 'id', 'F1' ) ) )
		);
	}

	public function testFormWithRepresentation_SerializesFromRepresentation() {
		$lexeme = NewLexeme::havingForm(
			NewForm::havingRepresentation( 'en', 'some representation' )
		)->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		$formSerialization = $serialization['forms'][0];
		assertThat(
			$formSerialization,
			hasKeyValuePair(
				'representations',
				hasKeyValuePair( 'en', 'some representation' )
			)
		);
	}

	public function testSerializesStatementsOnForms() {
		$lexeme = NewLexeme::havingForm(
			NewForm::havingStatement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) )
		)->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( "forms",
			hasItemInArray(
				hasKeyValuePair( "claims", equalTo( "P2" ) ) ) ) );
	}

	public function testSerializeGrammaticalFeaturesOnForms() {
		$lexeme = NewLexeme::havingForm(
			NewForm::havingGrammaticalFeature( 'Q1' )
		)->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( "forms",
			 hasItemInArray(
					hasKeyValuePair( "grammaticalFeatures", equalTo( [ 'Q1' ] ) )
			 )
		) );
	}

	public function testSerializeSensesIds() {
		$lexeme = NewLexeme::create()
			->withSense( NewSense::havingId( 'S1' ) )
			->withSense( NewSense::havingId( 'S2' ) )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( "senses",
			 hasItems(
				 hasKeyValuePair( "id", 'S1' ),
				 hasKeyValuePair( "id", 'S2' )
			 )
		) );
	}

	public function testSerializeGlossesOnSenses() {
		$lexeme = NewLexeme::create()
			->withSense(
				NewSense::havingId( 'S1' )
					->withGloss( 'en', 'en gloss' )
					->withGloss( 'fr', 'fr gloss' )
			)
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( "senses",
			 hasItemInArray(
				 hasKeyValuePair( "glosses",
					  both(
						  hasKeyValuePair( 'en', 'en gloss' )
					  )->andAlso(
						  hasKeyValuePair( 'fr', 'fr gloss' )
					  )
				 )
			 )
		) );
	}

	public function testSerializesStatementsOnSenses() {
		$lexeme = NewLexeme::create()
			->withSense(
				NewSense::havingStatement( new PropertyId( 'P2' ) )
			)
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat(
			$serialization,
			hasKeyValuePair(
				'senses',
				hasItemInArray(
					hasKeyValuePair( 'claims', 'P2' )
				)
			)
		);
	}

	public function testDoesNotSerializeNextFormId() {
		$lexeme = NewLexeme::create()->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, not( hasKeyInArray( 'nextFormId' ) ) );
	}

}

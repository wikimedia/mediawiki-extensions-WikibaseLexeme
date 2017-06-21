<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use PHPUnit_Framework_TestCase;
use Serializers\Exceptions\SerializationException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeForm;
use Wikibase\Lexeme\DataModel\LexemeFormId;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikibase\Lexeme\DataModel\Serialization\LexemeSerializer;
use Wikibase\Lexeme\Tests\DataModel\LexemeBuilder;
use Wikibase\Lexeme\Tests\DataModel\NewSense;

/**
 * @covers Wikibase\Lexeme\DataModel\Serialization\LexemeSerializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeSerializerTest extends PHPUnit_Framework_TestCase {

	private function newSerializer() {
		$statementListSerializer = $this->getMockBuilder( StatementListSerializer::class )
			->disableOriginalConstructor()
			->getMock();
		$statementListSerializer->method( 'serialize' )
			->will( $this->returnCallback( function( StatementList $statementList ) {
				return implode( '|', $statementList->getPropertyIds() );
			} ) );

		$termListSerializer = $this->getMockBuilder( TermListSerializer::class )
			->disableOriginalConstructor()
			->getMock();
		$termListSerializer->method( 'serialize' )
			->will( $this->returnCallback( function( TermList $termList ) {
				return $termList->toTextArray();
			} ) );

		return new LexemeSerializer( $termListSerializer, $statementListSerializer );
	}

	public function testSerializationOrder() {
		$lexeme = LexemeBuilder::create()
			->withId( 'L1' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		$this->assertSame(
			[ 'type', 'id', 'lexicalCategory', 'language', 'claims', 'forms', 'senses' ],
			array_keys( $serialization )
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
		$lexeme = LexemeBuilder::create()->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'type', 'lexeme' ) );
	}

	public function testLexemeWithLexicalCategory_SerializesLexicalCategory() {
		$lexeme = LexemeBuilder::create()
			->withLexicalCategory( 'Q1' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'lexicalCategory', 'Q1' ) );
	}

	public function testLexemeWithLanguage_SerializesLanguage() {
		$lexeme = LexemeBuilder::create()
			->withLanguage( 'Q2' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'language', 'Q2' ) );
	}

	public function testLexemeWithId_SerializesId() {
		$lexeme = LexemeBuilder::create()
			->withId( 'L1' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'id', 'L1' ) );
	}

	public function testLexemeWithStatements_SerializesStatements() {
		$lexeme = LexemeBuilder::create()
			->withStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'claims', 'P1' ) );
	}

	public function testLexemeWithLemmas_SerializesLemmas() {
		$lexeme = LexemeBuilder::create()
			->withLemma( 'ja', 'Tokyo' )
			->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'lemmas', hasKeyValuePair( 'ja', 'Tokyo' ) ) );
	}

	public function testLexemeWithoutForms_LexemeSerializationEmptyArrayAsForms() {
		$lexeme = LexemeBuilder::create()->build();

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( 'forms', emptyArray() ) );
	}

	public function testLexemeHasFormWithId_LexemeSerializationHasFormWithThatId() {
		$lexeme = LexemeBuilder::create()->build();
		$lexeme->setForms( [ new LexemeForm( new LexemeFormId( 'F1' ), '', [] ) ] );

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat(
			$serialization,
			hasKeyValuePair( 'forms', hasItemInArray( hasKeyValuePair( 'id', 'F1' ) ) )
		);
	}

	public function testLexemeFormWithRepresentation_SerializesFromRepresentation() {
		$lexeme = LexemeBuilder::create()->build();
		$lexeme->setForms( [ new LexemeForm( null, 'some representation', [] ) ] );

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat(
			$serialization,
			hasKeyValuePair(
				'forms',
				hasItemInArray(
					hasKeyValuePair( 'representation', 'some representation' )
				)
			)
		);
	}

	public function testSerializesStatementsOnLexemeForms() {
		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) );
		$forms = [ new LexemeForm(
			null,
			'some representation',
			[],
			new StatementList( [ $statement ] )
		) ];
		$lexeme = LexemeBuilder::create()->build();
		$lexeme->setForms( $forms );

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( "forms",
			hasItemInArray(
				hasKeyValuePair( "claims", equalTo( "P2" ) ) ) ) );
	}

	public function testSerializeGrammaticalFeaturesOnLexemeForms() {
		$forms = [ new LexemeForm(
			null,
			'some representation',
			[ new ItemId( 'Q1' ) ]
		) ];
		$lexeme = LexemeBuilder::create()->build();
		$lexeme->setForms( $forms );

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( "forms",
			 hasItemInArray(
					hasKeyValuePair( "grammaticalFeatures", equalTo( [ 'Q1' ] ) )
			 )
		) );
	}

	public function testSerializeSensesIds() {
		$lexeme = LexemeBuilder::create()
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
		$lexeme = LexemeBuilder::create()
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
		$lexeme = LexemeBuilder::create()
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

}

<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\NumberValue;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Serialization\LexemeDeserializer;
use Wikibase\Lexeme\DataModel\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;
use Wikibase\Lexeme\Tests\ErisGenerators\ErisTest;
use Wikibase\Lexeme\Tests\ErisGenerators\WikibaseLexemeGenerators;

/**
 * @covers \Wikibase\Lexeme\DataModel\Serialization\StorageLexemeSerializer
 *
 * @license GPL-2.0+
 */
class StorageLexemeSerializerTest extends \PHPUnit_Framework_TestCase {

	use ErisTest;

	public function testSerializedLexemeIsDeserializedToTheSameLexeme() {
		$serializer = $this->createSerializer();
		$deserializer = $this->createDeserializer();

		$this->eris()
			->forAll( WikibaseLexemeGenerators::lexeme() )
			->then( function ( Lexeme $lexeme ) use ( $serializer, $deserializer ) {
				$serialized = $serializer->serialize( $lexeme );
				$newLexeme = $deserializer->deserialize( $serialized );

				$this->assertTrue( $newLexeme->equals( $lexeme ), 'Lexemes are not equal' );
				$this->assertEquals( $lexeme, $newLexeme ); //Just to be sure
			} );
	}

	public function testSerializedLexemeIsDeserializedToTheSameLexeme_FullRoundTrip() {
		$serializer = $this->createSerializer();
		$deserializer = $this->createDeserializer();

		$this->eris()
			->limitTo( 10 )
			->forAll( WikibaseLexemeGenerators::lexeme() )
			->then( function ( Lexeme $lexeme ) use ( $serializer, $deserializer ) {
				$serialized = $serializer->serialize( $lexeme );
				// emulating full round trip
				$serialized = json_decode( json_encode( $serialized ), true );
				$newLexeme = $deserializer->deserialize( $serialized );

				$this->assertTrue( $newLexeme->equals( $lexeme ), 'Lexemes are not equal' );
				$this->assertEquals( $lexeme, $newLexeme ); //Just to be sure
			} );
	}

	public function testSerializationIsStable() {
		/**
		 * The purpose of this test is to insure against accidental serialization change.
		 * This is important because entities are stored as text (JSON encoded structure)
		 * and migration of serialization is close to impossible.
		 */

		$serializer = $this->createSerializer();

		$lexeme = NewLexeme::havingId( new LexemeId( 'L1' ) )
			->withLanguage( 'Q1' )
			->withLexicalCategory( 'Q2' )
			->withLemma( 'en', 'color' )
			->withLemma( 'en_gb', 'colour' )
			->withForm(
				NewForm::havingId( 'F1' )
					->andRepresentation( 'en', 'color' )
					->andRepresentation( 'en_gb', 'colour' )
					->andGrammaticalFeature( 'Q3' )
					->andStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) )
			)->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'colors' )
					->andRepresentation( 'en_gb', 'colours' )
					->andGrammaticalFeature( 'Q4' )
					->andStatement( new PropertySomeValueSnak( new PropertyId( 'P2' ) ) )
			)->withSense(
				NewSense::havingId( 'S1' )
					->withGloss(
						'en',
						'the property of an object of producing different sensations on the eye'
					)->withStatement(
						new PropertyValueSnak(
							new PropertyId( 'P3' ),
							new EntityIdValue( new ItemId( 'Q5' ) )
						)
					)
			)->withStatement( new PropertySomeValueSnak( new PropertyId( 'P4' ) ) )
			->withStatement( new PropertyNoValueSnak( new PropertyId( 'P5' ) ) )
			->build();

		$lexemeSerialization = $serializer->serialize( $lexeme );

		$expectedSerialization = [
			'type' => 'lexeme',
			'id' => 'L1',
			'lemmas' => [
				'en' => [ 'language' => 'en', 'value' => 'color' ],
				'en_gb' => [ 'language' => 'en_gb', 'value' => 'colour' ],
			],
			'lexicalCategory' => 'Q2',
			'language' => 'Q1',
			'claims' => [
				'P4' => [ [
					'mainsnak' => [
						'snaktype' => 'somevalue',
						'property' => 'P4',
						'hash' => 'ede91cc55952400386a2401405bb09e446b1867b',
					],
					'type' => 'statement',
					'rank' => 'normal',
				] ],
				'P5' => [ [
					'mainsnak' => [
						'snaktype' => 'novalue',
						'property' => 'P5',
						'hash' => '6ee46c6e25606f949a870c84ae6694be8b5d4a02',
					],
					'type' => 'statement',
					'rank' => 'normal',
				] ],
			],
			'nextFormId' => 3,
			'forms' => [
				[
					'id' => 'L1-F1',
					'representations' => [
						'en' => [ 'language' => 'en', 'value' => 'color' ],
						'en_gb' => [ 'language' => 'en_gb', 'value' => 'colour' ],
					],
					'grammaticalFeatures' => [ 'Q3' ],
					'claims' => [
						'P1' => [ [
							'mainsnak' => [
								'snaktype' => 'novalue',
								'property' => 'P1',
								'hash' => 'c77761897897f63f151c4a1deb8bd3ad23ac51c6',
							],
							'type' => 'statement',
							'rank' => 'normal',
						], ],
					],
				],
				[
					'id' => 'L1-F2',
					'representations' => [
						'en' => [ 'language' => 'en', 'value' => 'colors' ],
						'en_gb' => [ 'language' => 'en_gb', 'value' => 'colours' ],
					],
					'grammaticalFeatures' => [ 'Q4' ],
					'claims' => [
						'P2' => [ [
							'mainsnak' => [
								'snaktype' => 'somevalue',
								'property' => 'P2',
								'hash' => '0cf42a63838da890a0b23c220bdb7705ca9c7892',
							],
							'type' => 'statement',
							'rank' => 'normal',
						] ],
					],
				],
			],
			'senses' => [
				[
					'id' => 'S1',
					'glosses' => [
						'en' => [
							'language' => 'en',
							'value' => 'the property of an object of producing' .
								' different sensations on the eye',
						],
					],
					'claims' => [
						'P3' => [ [
							'mainsnak' => [
								'snaktype' => 'value',
								'property' => 'P3',
								'hash' => '4c2e17bc6d4c930be2beccbf929724b2cd431f3d',
								'datavalue' => [
									'value' => [
										'entity-type' => 'item',
										'numeric-id' => 5,
										'id' => 'Q5',
									],
									'type' => 'wikibase-entityid',
								],
							],
							'type' => 'statement',
							'rank' => 'normal',
						] ],
					]
				]
			]
		];

		$this->assertEquals( $expectedSerialization, $lexemeSerialization );
	}

	/**
	 * @return StorageLexemeSerializer
	 */
	private function createSerializer() {
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_DEFAULT
		);
		return new StorageLexemeSerializer(
			new TermListSerializer( new TermSerializer(), false ),
			$serializerFactory->newStatementListSerializer()
		);
	}

	/**
	 * @return LexemeDeserializer
	 */
	private function createDeserializer() {
		$entityIdParser = new DispatchingEntityIdParser(
			[
				LexemeId::PATTERN => function ( $s ) {
					return new LexemeId( $s );
				},
				ItemId::PATTERN => function ( $s ) {
					return new ItemId( $s );
				},
				PropertyId::PATTERN => function ( $s ) {
					return new PropertyId( $s );
				}
			]
		);
		$factory = new DeserializerFactory(
			$this->newDataValueDeserializer(),
			$entityIdParser
		);
		$statementListDeserializer = $factory->newStatementListDeserializer();
		return new LexemeDeserializer(
			new EntityIdDeserializer( $entityIdParser ),
			$statementListDeserializer
		);
	}

	/**
	 * @return DataValueDeserializer
	 */
	private function newDataValueDeserializer() {
		return new DataValueDeserializer(
			[
				'number' => NumberValue::class,
				'string' => StringValue::class,
				'wikibase-entityid' => EntityIdValue::class,
			]
		);
	}

}

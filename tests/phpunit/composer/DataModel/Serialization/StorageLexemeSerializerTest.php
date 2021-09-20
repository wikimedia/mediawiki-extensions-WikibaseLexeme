<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\NumberValue;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Serialization\LexemeDeserializer;
use Wikibase\Lexeme\Serialization\StorageLexemeSerializer;
use Wikibase\Lexeme\Tests\ErisGenerators\ErisTest;
use Wikibase\Lexeme\Tests\ErisGenerators\WikibaseLexemeGenerators;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\Serialization\StorageLexemeSerializer
 *
 * @license GPL-2.0-or-later
 */
class StorageLexemeSerializerTest extends TestCase {

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
					->andStatement( new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ) )
			)->withForm(
				NewForm::havingId( 'F2' )
					->andRepresentation( 'en', 'colors' )
					->andRepresentation( 'en_gb', 'colours' )
					->andGrammaticalFeature( 'Q4' )
					->andStatement( new PropertySomeValueSnak( new NumericPropertyId( 'P2' ) ) )
			)->withSense(
				NewSense::havingId( 'S1' )
					->withGloss(
						'en',
						'the property of an object of producing different sensations on the eye'
					)->withStatement(
						new PropertyValueSnak(
							new NumericPropertyId( 'P3' ),
							new EntityIdValue( new ItemId( 'Q5' ) )
						)
					)
			)->withStatement( new PropertySomeValueSnak( new NumericPropertyId( 'P4' ) ) )
			->withStatement( new PropertyNoValueSnak( new NumericPropertyId( 'P5' ) ) )
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
			'nextSenseId' => 2,
			'senses' => [
				[
					'id' => 'L1-S1',
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
			$serializerFactory->newTermListSerializer(),
			$serializerFactory->newStatementListSerializer()
		);
	}

	/**
	 * @return LexemeDeserializer
	 */
	private function createDeserializer() {
		$entityIdParser = new DispatchingEntityIdParser(
			[
				LexemeId::PATTERN => static function ( $s ) {
					return new LexemeId( $s );
				},
				ItemId::PATTERN => static function ( $s ) {
					return new ItemId( $s );
				},
				NumericPropertyId::PATTERN => static function ( $s ) {
					return new NumericPropertyId( $s );
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

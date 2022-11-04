<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use Deserializers\Exceptions\DeserializationException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikibase\Lexeme\Serialization\LexemeDeserializer;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\Serialization\LexemeDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDeserializerTest extends TestCase {

	private function newDeserializer() {
		$entityIdDeserializer = $this->createMock( EntityIdDeserializer::class );
		$entityIdDeserializer->method( 'deserialize' )
			->will( $this->returnCallback( static function ( $serialization ) {
				return new ItemId( $serialization );
			} ) );

		$statementListDeserializer = $this->createMock( StatementListDeserializer::class );
		$statementListDeserializer->method( 'deserialize' )
			->will( $this->returnCallback( static function ( array $serialization ) {
				$statementList = new StatementList();

				foreach ( $serialization as $propertyId => $propertyStatements ) {
					foreach ( $propertyStatements as $ignoredStatementData ) {
						$statementList->addNewStatement(
							new PropertyNoValueSnak( new NumericPropertyId( $propertyId ) )
						);
					}
				}

				return $statementList;
			} ) );

		return new LexemeDeserializer(
			$entityIdDeserializer,
			$statementListDeserializer
		);
	}

	public function provideObjectSerializations() {
		$serializations = [];

		$serializations['empty'] = [
			[ 'type' => 'lexeme', 'nextFormId' => 1, ],
			new Lexeme()
		];

		$serializations['empty lists'] = [
			[
				'type' => 'lexeme',
				'claims' => [],
				'nextFormId' => 1,
			],
			new Lexeme()
		];

		$serializations['with id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L1',
				'nextFormId' => 1,
			],
			new Lexeme( new LexemeId( 'L1' ) )
		];

		$serializations['with id and empty lists'] = [
			[
				'type' => 'lexeme',
				'id' => 'L1',
				'claims' => [],
				'nextFormId' => 1,
			],
			new Lexeme( new LexemeId( 'L1' ) )
		];

		$lexeme = new Lexeme();
		$lexeme->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) )
		);

		$serializations['with content'] = [
			[
				'type' => 'lexeme',
				'claims' => [ 'P42' => [ 'STATEMENT DATA' ] ],
				'nextFormId' => 1,
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) )
		);

		$serializations['with content and id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'claims' => [ 'P42' => [ 'STATEMENT DATA' ] ],
				'nextFormId' => 1,
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->setLemmas( new TermList( [ new Term( 'el', 'Hey' ) ] ) );

		$serializations['with content and lemmas'] = [
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lemmas' => [ 'el'  => [ 'language' => 'el', 'value' => 'Hey' ] ],
				'nextFormId' => 1,
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->setLexicalCategory( new ItemId( 'Q33' ) );
		$serializations['with lexical category and id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lexicalCategory' => 'Q33',
				'nextFormId' => 1,
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l3' ) );
		$lexeme->setLanguage( new ItemId( 'Q11' ) );
		$serializations['with language and id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L3',
				'language' => 'Q11',
				'nextFormId' => 1,
			],
			$lexeme
		];

		$serializations['with minimal forms'] = [
			[
				'type' => 'lexeme',
				'id' => 'L1',
				'lexicalCategory' => 'Q1',
				'language' => 'Q2',
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'form' ] ],
				'nextFormId' => 2,
				'forms' => [
					[
						'id' => 'L1-F1',
						'representations' => [
							'en' => [ 'language' => 'en', 'value' => 'form' ]
						],
						'grammaticalFeatures' => [],
						'claims' => [],
					]
				],
			],
			NewLexeme::havingId( 'L1' )
				->withLexicalCategory( 'Q1' )
				->withLanguage( 'Q2' )
				->withLemma( 'en', 'form' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'form' )
				)->build()

		];

		$serializations['with statement on a form'] = [
			[
				'type' => 'lexeme',
				'id' => 'L1',
				'lexicalCategory' => 'Q1',
				'language' => 'Q2',
				'lemmas' => [ 'en' => [ 'language' => 'en', 'value' => 'form' ] ],
				'nextFormId' => 2,
				'forms' => [
					[
						'id' => 'L1-F1',
						'representations' => [
							'en' => [ 'language' => 'en', 'value' => 'form' ]
						],
						'grammaticalFeatures' => [],
						'claims' => [ 'P42' => [ 'STATEMENT DATA' ] ],
					]
				],
			],
			NewLexeme::havingId( 'L1' )
				->withLexicalCategory( 'Q1' )
				->withLanguage( 'Q2' )
				->withLemma( 'en', 'form' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'form' )
						->andStatement( NewStatement::noValueFor( new NumericPropertyId( 'P42' ) )->build() )
				)->build()

		];

		$serializations['with empty senses list and no nextSenseId'] = [
			[
				'type' => 'lexeme',
				'nextFormId' => 1,
				'senses' => [],
			],
			new Lexeme()
		];

		$serializations['with empty senses list and default nextSenseId'] = [
			[
				'type' => 'lexeme',
				'nextFormId' => 1,
				'nextSenseId' => 1,
				'senses' => [],
			],
			new Lexeme()
		];

		$serializations['with empty senses list and non-default nextSenseId'] = [
			[
				'type' => 'lexeme',
				'nextFormId' => 1,
				'nextSenseId' => 2,
				'senses' => [],
			],
			new Lexeme( null, null, null, null, null, 1, null, 2 )
		];

		$serializations['with minimal sense'] = [
			[
				'type' => 'lexeme',
				'nextFormId' => 1,
				'nextSenseId' => 2,
				'senses' => [
					[
						'id' => 'L1-S1',
						'glosses' => [
							'en' => [ 'language' => 'en', 'value' => 'gloss' ],
						],
						'claims' => [],
					],
				],
			],
			new Lexeme( null, null, null, null, null, 1, null, 2, new SenseSet( [
				new Sense(
					new SenseId( 'L1-S1' ),
					new TermList( [ new Term( 'en', 'gloss' ) ] )
				),
			] ) )
		];

		$serializations['with statement on a sense'] = [
			[
				'type' => 'lexeme',
				'nextFormId' => 1,
				'nextSenseId' => 2,
				'senses' => [
					[
						'id' => 'L1-S1',
						'glosses' => [
							'en' => [ 'language' => 'en', 'value' => 'gloss' ],
						],
						'claims' => [ 'P42' => [ 'STATEMENT DATA' ] ],
					],
				],
			],
			new Lexeme( null, null, null, null, null, 1, null, 2, new SenseSet( [
				new Sense(
					new SenseId( 'L1-S1' ),
					new TermList( [ new Term( 'en', 'gloss' ) ] ),
					new StatementList( NewStatement::noValueFor( new NumericPropertyId( 'P42' ) )->build() )
				),
			] ) )
		];

		$serializations['with multiple non-consecutive senses'] = [
			[
				'type' => 'lexeme',
				'nextFormId' => 1,
				'nextSenseId' => 7,
				'senses' => [
					[
						'id' => 'L1-S2',
						'glosses' => [
							'en' => [ 'language' => 'en', 'value' => 'gloss' ],
						],
						'claims' => [],
					],
					[
						'id' => 'L1-S5',
						'glosses' => [
							'de' => [ 'language' => 'de', 'value' => 'Glosse' ],
						],
						'claims' => [],
					],
				],
			],
			new Lexeme( null, null, null, null, null, 1, null, 7, new SenseSet( [
				new Sense(
					new SenseId( 'L1-S2' ),
					new TermList( [ new Term( 'en', 'gloss' ) ] )
				),
				new Sense(
					new SenseId( 'L1-S5' ),
					new TermList( [ new Term( 'de', 'Glosse' ) ] )
				),
			] ) )
		];

		return $serializations;
	}

	/**
	 * @dataProvider provideObjectSerializations
	 */
	public function testDeserialize( array $serialization, Lexeme $lexeme ) {
		$deserializer = $this->newDeserializer();

		$this->assertEquals( $lexeme, $deserializer->deserialize( $serialization ) );
	}

	/**
	 * @dataProvider provideObjectSerializations
	 */
	public function testIsDeserializerFor( array $serialization ) {
		$deserializer = $this->newDeserializer();

		$this->assertTrue( $deserializer->isDeserializerFor( $serialization ) );
	}

	public function provideInvalidSerializations() {
		return [
			[ null ],
			[ '' ],
			[ [] ],
			[ [ 'foo' => 'bar' ] ],
			[ [ 'type' => null ] ],
			[ [ 'type' => 'item' ] ]
		];
	}

	/**
	 * @dataProvider provideInvalidSerializations
	 */
	public function testDeserializeException( $serialization ) {
		$deserializer = $this->newDeserializer();

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( $serialization );
	}

	/**
	 * @dataProvider provideInvalidSerializations
	 */
	public function testIsNotDeserializerFor( $serialization ) {
		$deserializer = $this->newDeserializer();

		$this->assertFalse( $deserializer->isDeserializerFor( $serialization ) );
	}

	public function testDeserializesNewFormId() {
		$serialization = $this->getMinimalValidSerialization();
		$serialization['nextFormId'] = 4;

		/** @var Lexeme $lexeme */
		$lexeme = $this->newDeserializer()->deserialize( $serialization );

		$this->assertEquals( 4, $lexeme->getNextFormId() );
	}

	private function getMinimalValidSerialization() {
		return [
			'type' => 'lexeme',
			'id' => 'L2',
			'lexicalCategory' => 'Q1',
			'language' => 'Q2',
			'lemmas' => [ 'el' => [ 'language' => 'el', 'value' => 'Hey' ] ],
			'nextFormId' => 1,
			"forms" => [],
			"senses" => []
		];
	}

}

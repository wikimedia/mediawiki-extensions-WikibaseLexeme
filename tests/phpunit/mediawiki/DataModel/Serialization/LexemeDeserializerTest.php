<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use Deserializers\Exceptions\DeserializationException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Serialization\LexemeDeserializer;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\DataModel\Serialization\LexemeDeserializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDeserializerTest extends PHPUnit_Framework_TestCase {

	private function newDeserializer() {
		$entityIdDeserializer = $this->getMockBuilder( EntityIdDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
		$entityIdDeserializer->method( 'deserialize' )
			->will( $this->returnCallback( function ( $serialization ) {
				return new ItemId( $serialization );
			} ) );

		$statementListDeserializer = $this->getMockBuilder( StatementListDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
		$statementListDeserializer->method( 'deserialize' )
			->will( $this->returnCallback( function ( array $serialization ) {
				$statementList = new StatementList();

				foreach ( $serialization as $propertyId ) {
					$statementList->addNewStatement( new PropertyNoValueSnak( $propertyId ) );
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
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$serializations['with content'] = [
			[
				'type' => 'lexeme',
				'claims' => [ 42 ],
				'nextFormId' => 1,
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$serializations['with content and id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'claims' => [ 42 ],
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
				'nextFormId' => 2,
				'forms' => [
					[
						'id' => 'F1',
						'representations' => [
							'en' => [ 'language' => 'en', 'value' => 'form' ]
						],
						'grammaticalFeatures' => []
					]
				],
			],
			NewLexeme::havingId( 'L1' )
				->withLexicalCategory( 'Q1' )
				->withLanguage( 'Q2' )
				->withForm(
					NewForm::havingId( 'F1' )
						->andRepresentation( 'en', 'form' )
				)->build()

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

		$this->setExpectedException( DeserializationException::class );
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

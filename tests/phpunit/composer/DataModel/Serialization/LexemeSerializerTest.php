<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use PHPUnit_Framework_TestCase;
use Serializers\Exceptions\SerializationException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Serialization\LexemeSerializer;

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
		$statementListSerializer->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( function( StatementList $statementList ) {
				return implode( '|', $statementList->getPropertyIds() );
			} ) );

		$termListSerializer = $this->getMockBuilder( TermListSerializer::class )
			->disableOriginalConstructor()
			->getMock();
		$termListSerializer->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( function( TermList $termList ) {
				return $termList->toTextArray();
			} ) );

		return new LexemeSerializer( $termListSerializer, $statementListSerializer );
	}

	public function provideObjectSerializations() {
		$serializations = [];

		$lexeme = new Lexeme();

		$serializations['empty'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'claims' => '',
			]
		];

		$lexeme = new Lexeme( new LexemeId( 'L1' ) );

		$serializations['with id'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L1',
				'claims' => '',
			]
		];

		$lexeme = new Lexeme();
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$serializations['with content'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'claims' => 'P42',
			]
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$serializations['with content and id'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'claims' => 'P42',
			]
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->setLemmas( new TermList( [ new Term( 'ja', 'Tokyo' ) ] ) );

		$serializations['with lemmas and id'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lemmas' => [ 'ja' => 'Tokyo' ],
				'claims' => '',
			]
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->setLexicalCategory( new ItemId( 'Q32' ) );

		$serializations['with lexical category and id'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lexicalCategory' => 'Q32',
				'claims' => '',
			]
		];

		$lexeme = new Lexeme( new LexemeId( 'l3' ) );
		$lexeme->setLanguage( new ItemId( 'Q11' ) );

		$serializations['with language and id'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L3',
				'language' => 'Q11',
				'claims' => '',
			]
		];

		return $serializations;
	}

	/**
	 * @dataProvider provideObjectSerializations
	 */
	public function testSerialize( Lexeme $lexeme, array $serialization ) {
		$serializer = $this->newSerializer();

		$this->assertSame( $serialization, $serializer->serialize( $lexeme ) );
	}

	public function testSerializationOrder() {
		$lexeme = new Lexeme( new LexemeId( 'L1' ) );
		$serialization = $this->newSerializer()->serialize( $lexeme );

		$this->assertSame(
			[ 'type', 'id', 'claims' ],
			array_keys( $serialization )
		);
	}

	/**
	 * @dataProvider provideObjectSerializations
	 */
	public function testIsSerializerFor( Lexeme $lexeme ) {
		$serializer = $this->newSerializer();

		$this->assertTrue( $serializer->isSerializerFor( $lexeme ) );
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

}

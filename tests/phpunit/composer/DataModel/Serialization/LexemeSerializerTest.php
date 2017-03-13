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

		$lexicalCategory = new ItemId( 'Q32' );
		$language = new ItemId( 'Q11' );
		$lexeme = new Lexeme( null, null, $lexicalCategory, $language );

		$serializations['empty'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'lexicalCategory' => 'Q32',
				'language' => 'Q11',
				'claims' => '',
			]
		];

		$lexeme = new Lexeme( new LexemeId( 'L1' ), null, $lexicalCategory, $language );

		$serializations['with id'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L1',
				'lexicalCategory' => 'Q32',
				'language' => 'Q11',
				'claims' => '',
			]
		];

		$lexeme = new Lexeme( null, null, $lexicalCategory, $language );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$serializations['with content'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'lexicalCategory' => 'Q32',
				'language' => 'Q11',
				'claims' => 'P42',
			]
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ), null, $lexicalCategory, $language );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$serializations['with content and id'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lexicalCategory' => 'Q32',
				'language' => 'Q11',
				'claims' => 'P42',
			]
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ), null, $lexicalCategory, $language );
		$lexeme->setLemmas( new TermList( [ new Term( 'ja', 'Tokyo' ) ] ) );

		$serializations['with lemmas and id'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lemmas' => [ 'ja' => 'Tokyo' ],
				'lexicalCategory' => 'Q32',
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
		$lexicalCategory = new ItemId( 'Q32' );
		$language = new ItemId( 'Q11' );
		$lexeme = new Lexeme( new LexemeId( 'L1' ), null, $lexicalCategory, $language );
		$serialization = $this->newSerializer()->serialize( $lexeme );

		$this->assertSame(
			[ 'type', 'id', 'lexicalCategory', 'language', 'claims' ],
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

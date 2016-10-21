<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use PHPUnit_Framework_TestCase;
use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
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
		$statementListSerializer = $this->getMock( Serializer::class );
		$statementListSerializer->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( function( StatementList $statementList ) {
				return implode( '|', $statementList->getPropertyIds() );
			} ) );

		return new LexemeSerializer( $statementListSerializer );
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

		return $serializations;
	}

	/**
	 * @dataProvider provideObjectSerializations
	 */
	public function testSerialize( $object, $serialization ) {
		$serializer = $this->newSerializer();

		$this->assertSame( $serialization, $serializer->serialize( $object ) );
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
	public function testIsSerializerFor( $object ) {
		$serializer = $this->newSerializer();

		$this->assertTrue( $serializer->isSerializerFor( $object ) );
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

<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use Deserializers\Exceptions\DeserializationException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Serialization\LexemeDeserializer;

/**
 * @covers Wikibase\Lexeme\DataModel\Serialization\LexemeDeserializer
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDeserializerTest extends PHPUnit_Framework_TestCase {

	private function newDeserializer() {
		$statementListDeserializer = $this->getMockBuilder( StatementListDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
		$statementListDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnCallback( function( array $serialization ) {
				$statementList = new StatementList();

				foreach ( $serialization as $propertyId ) {
					$statementList->addNewStatement( new PropertyNoValueSnak( $propertyId ) );
				}

				return $statementList;
			} ) );

		$termListDeserializer = $this->getMockBuilder( TermListDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
		$termListDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnCallback( function( array $serialization ) {
				$terms = [];
				foreach ( $serialization as $language => $value ) {
					$terms[] = new Term( $language, $value );
				}
				return new TermList( $terms );
			} ) );

		return new LexemeDeserializer( $termListDeserializer, $statementListDeserializer );
	}

	public function provideObjectSerializations() {
		$serializations = [];

		$serializations['empty'] = [
			[ 'type' => 'lexeme' ],
			new Lexeme()
		];

		$serializations['empty lists'] = [
			[
				'type' => 'lexeme',
				'claims' => []
			],
			new Lexeme()
		];

		$serializations['with id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L1'
			],
			new Lexeme( new LexemeId( 'L1' ) )
		];

		$serializations['with id and empty lists'] = [
			[
				'type' => 'lexeme',
				'id' => 'L1',
				'claims' => []
			],
			new Lexeme( new LexemeId( 'L1' ) )
		];

		$lexeme = new Lexeme();
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$serializations['with content'] = [
			[
				'type' => 'lexeme',
				'claims' => [ 42 ]
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$serializations['with content and id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'claims' => [ 42 ]
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->setLemmata( new TermList( [ new Term( 'el', 'Hey' ) ] ) );

		$serializations['with content and lemmata'] = [
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lemmata' => [ 'el'  => 'Hey' ],
			],
			$lexeme
		];

		return $serializations;
	}

	/**
	 * @dataProvider provideObjectSerializations
	 */
	public function testDeserialize( $serialization, $object ) {
		$deserializer = $this->newDeserializer();

		$this->assertEquals( $object, $deserializer->deserialize( $serialization ) );
	}

	/**
	 * @dataProvider provideObjectSerializations
	 */
	public function testIsDeserializerFor( $serialization ) {
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

}

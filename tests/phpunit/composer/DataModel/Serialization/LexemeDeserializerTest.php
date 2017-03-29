<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use Deserializers\Exceptions\DeserializationException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Entity\ItemId;
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

		$entityIdDeserializer = $this->getMockBuilder( EntityIdDeserializer::class )
			->disableOriginalConstructor()
			->getMock();
		$entityIdDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnCallback( function( $serialization ) {
				return new ItemId( $serialization );
			} ) );

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

		return new LexemeDeserializer(
			$entityIdDeserializer,
			$termListDeserializer,
			$statementListDeserializer
		);
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
		$lexeme->setLemmas( new TermList( [ new Term( 'el', 'Hey' ) ] ) );

		$serializations['with content and lemmas'] = [
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lemmas' => [ 'el'  => 'Hey' ],
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l2' ) );
		$lexeme->setLexicalCategory( new ItemId( 'Q33' ) );
		$serializations['with lexical category and id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L2',
				'lexicalCategory' => 'Q33'
			],
			$lexeme
		];

		$lexeme = new Lexeme( new LexemeId( 'l3' ) );
		$lexeme->setLanguage( new ItemId( 'Q11' ) );
		$serializations['with language and id'] = [
			[
				'type' => 'lexeme',
				'id' => 'L3',
				'language' => 'Q11'
			],
			$lexeme
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

}

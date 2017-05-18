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
				'forms' => [],
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
				'forms' => [],
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
				'forms' => [],
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
				'forms' => [],
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
				'forms' => [],
			]
		];

		$forms = [ new LexemeForm( null, 'form', [] ) ];
		$lexeme = new Lexeme( null, null, $lexicalCategory, $language, null, $forms );
		$serializations['with minimal forms'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'lexicalCategory' => 'Q32',
				'language' => 'Q11',
				'claims' => '',
				'forms' => [ [ 'representation' => 'form', 'claims' => '' ] ],
			]
		];

		$forms = [ new LexemeForm( new LexemeFormId( 'F5' ), 'form', [] ) ];
		$lexeme = new Lexeme( new LexemeId( 'L5' ), null, $lexicalCategory, $language, null, $forms );
		$serializations['with forms and all IDs set'] = [
			$lexeme,
			[
				'type' => 'lexeme',
				'id' => 'L5',
				'lexicalCategory' => 'Q32',
				'language' => 'Q11',
				'claims' => '',
				'forms' => [ [
					'id' => 'F5',
					'representation' => 'form',
					'claims' => '',
				] ],
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
			[ 'type', 'id', 'lexicalCategory', 'language', 'claims', 'forms' ],
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

	public function testSerializesStatementsOnLexemeForms() {
		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) );
		$forms = [ new LexemeForm(
			null,
			'some representation',
			[],
			new StatementList( [ $statement ] )
		) ];
		$lexeme = new Lexeme( null, null, new ItemId( 'Q1' ), new ItemId( 'Q1' ), null, $forms );

		$serialization = $this->newSerializer()->serialize( $lexeme );

		assertThat( $serialization, hasKeyValuePair( "forms",
			hasItemInArray(
				hasKeyValuePair( "claims", equalTo( "P2" ) ) ) ) );
	}

}

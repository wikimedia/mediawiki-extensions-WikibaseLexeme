<?php

namespace Wikibase\Lexeme\Tests\Unit\ChangeOp\Deserialization;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ItemIdListDeserializer
 *
 * @license GPL-2.0-or-later
 */
class ItemIdListDeserializerTest extends MediaWikiUnitTestCase {

	public function testDeserializeEmptyArray_returnsEmptyArray() {
		$itemIdParser = $this->createMock( ItemIdParser::class );
		$itemIdParser
			->expects( $this->never() )
			->method( 'parse' );
		$derserializer = new ItemIdListDeserializer( $itemIdParser );

		$contextSpy = $this->createMock( ValidationContext::class );

		$this->assertSame( [], $derserializer->deserialize( [], $contextSpy ) );
	}

	public function testDeserializeArrayWithValidSerializations_returnsArrayOfItemIds() {
		$q3 = new ItemId( 'Q3' );
		$q7 = new ItemId( 'Q7' );

		$itemIdParser = $this->createMock( ItemIdParser::class );
		$itemIdParser->method( 'parse' )
			->will( $this->returnValueMap( [
				[ 'Q3', $q3 ],
				[ 'Q7', $q7 ]
			] ) );

		$contextSpy = $this->createMock( ValidationContext::class );
		$contextSpy
			->expects( $this->never() )
			->method( 'addViolation' );

		$derserializer = new ItemIdListDeserializer( $itemIdParser );

		$this->assertSame( [ $q3, $q7 ], $derserializer->deserialize( [ 'Q3', 'Q7' ], $contextSpy ) );
	}

	public function testDeserializeArrayWithInvalidSerializations_returnsEmptyArrayAndAddsViolation() {
		$itemIdParser = $this->createMock( ItemIdParser::class );
		$itemIdParser->method( 'parse' )
			->willThrowException( new EntityIdParsingException() );

		$contextSpy = $this->createMock( ValidationContext::class );
		$contextSpy
			->expects( $this->once() )
			->method( 'at' )
			->with( 0 )
			->willReturnSelf(); // not quite right, but good enough
		$contextSpy
			->expects( $this->once() )
			->method( 'addViolation' );

		$derserializer = new ItemIdListDeserializer( $itemIdParser );

		$this->assertSame( [], $derserializer->deserialize( [ 'qFoo' ], $contextSpy ) );
	}

}

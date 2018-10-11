<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotSenseId;
use Wikibase\Lexeme\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @covers \Wikibase\Lexeme\ChangeOp\Deserialization\SenseIdDeserializer
 *
 * @license GPL-2.0-or-later
 */
class SenseIdDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testDeserializeValidSenseId_returnsSenseId() {
		$senseId = new SenseId( 'L1-S1' );

		$entityIdParser = $this->getMock( EntityIdParser::class );
		$entityIdParser
			->method( 'parse' )
			->with( 'L1-S1' )
			->willReturn( $senseId );

		$context = $this->getContextSpy();
		$context
			->expects( $this->never() )
			->method( 'addViolation' );

		$deserializer = new SenseIdDeserializer( $entityIdParser );

		$this->assertSame( $senseId, $deserializer->deserialize( 'L1-S1', $context ) );
	}

	public function testDeserializeNotValidSenseId_returnsNullAndContextHasViolation() {
		$entityIdParser = $this->getMock( EntityIdParser::class );
		$entityIdParser
			->method( 'parse' )
			->with( 'somesome' )
			->willThrowException( new EntityIdParsingException( 'so sad' ) );

		$context = $this->getContextSpy();
		$context
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotSenseId( 'somesome' ) );

		$deserializer = new SenseIdDeserializer( $entityIdParser );

		$this->assertNull( $deserializer->deserialize( 'somesome', $context ) );
	}

	public function testDeserializeNonSenseReferencingSenseId_returnsNullAndContextHasViolation() {
		$senseId = $this->getMockBuilder( SenseId::class )
			->disableOriginalConstructor()
			->getMock();
		$senseId
			->method( 'getEntityType' )
			->willReturn( 'weird' );

		$entityIdParser = $this->getMock( EntityIdParser::class );
		$entityIdParser
			->method( 'parse' )
			->with( 'L1-S1' )
			->willReturn( $senseId );

		$context = $this->getContextSpy();
		$context
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotSenseId( 'L1-S1' ) );

		$deserializer = new SenseIdDeserializer( $entityIdParser );

		$this->assertNull( $deserializer->deserialize( 'L1-S1', $context ) );
	}

	private function getContextSpy() {
		return $this
			->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

}

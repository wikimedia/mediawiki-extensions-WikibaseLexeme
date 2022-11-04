<?php

namespace Wikibase\Lexeme\Tests\Unit\ChangeOp\Deserialization;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotSenseId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\SenseIdDeserializer
 *
 * @license GPL-2.0-or-later
 */
class SenseIdDeserializerTest extends MediaWikiUnitTestCase {

	public function testDeserializeValidSenseId_returnsSenseId() {
		$senseId = new SenseId( 'L1-S1' );

		$entityIdParser = $this->createMock( EntityIdParser::class );
		$entityIdParser->method( 'parse' )
			->with( 'L1-S1' )
			->willReturn( $senseId );

		$context = $this->createMock( ValidationContext::class );
		$context
			->expects( $this->never() )
			->method( 'addViolation' );

		$deserializer = new SenseIdDeserializer( $entityIdParser );

		$this->assertSame( $senseId, $deserializer->deserialize( 'L1-S1', $context ) );
	}

	public function testDeserializeNotValidSenseId_returnsNullAndContextHasViolation() {
		$entityIdParser = $this->createMock( EntityIdParser::class );
		$entityIdParser->method( 'parse' )
			->with( 'somesome' )
			->willThrowException( new EntityIdParsingException( 'so sad' ) );

		$context = $this->createMock( ValidationContext::class );
		$context
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotSenseId( 'somesome' ) );

		$deserializer = new SenseIdDeserializer( $entityIdParser );

		$this->assertNull( $deserializer->deserialize( 'somesome', $context ) );
	}

	public function testDeserializeNonSenseReferencingSenseId_returnsNullAndContextHasViolation() {
		$senseId = $this->createMock( SenseId::class );
		$senseId->method( 'getEntityType' )
			->willReturn( 'weird' );

		$entityIdParser = $this->createMock( EntityIdParser::class );
		$entityIdParser->method( 'parse' )
			->with( 'L1-S1' )
			->willReturn( $senseId );

		$context = $this->createMock( ValidationContext::class );
		$context
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotSenseId( 'L1-S1' ) );

		$deserializer = new SenseIdDeserializer( $entityIdParser );

		$this->assertNull( $deserializer->deserialize( 'L1-S1', $context ) );
	}

}

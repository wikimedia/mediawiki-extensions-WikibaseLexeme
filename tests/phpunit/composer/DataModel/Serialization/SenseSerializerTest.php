<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use PHPUnit\Framework\TestCase;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\Lexeme\Serialization\SenseSerializer;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\Serialization\SenseSerializer
 *
 * @license GPL-2.0-or-later
 */
class SenseSerializerTest extends TestCase {

	public function testGivenLexeme_isSerializerForReturnsFalse() {
		$serializer = new SenseSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$lexeme = NewLexeme::create()->build();
		$this->assertFalse( $serializer->isSerializerFor( $lexeme ) );
	}

	public function testGivenSense_isSerializerForReturnsTrue() {
		$serializer = new SenseSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$sense = NewSense::havingId( 'S1' )->build();
		$this->assertTrue( $serializer->isSerializerFor( $sense ) );
	}

	public function testGivenLexeme_serializeThrowsException() {
		$serializer = new SenseSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$lexeme = NewLexeme::create()->build();
		$this->expectException( UnsupportedObjectException::class );
		$serializer->serialize( $lexeme );
	}

	public function testGivenSense_serializeReturnsSerializedData() {
		$serializer = new SenseSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$sense = NewSense::havingId( 'S1' )->build();
		$this->assertIsArray( $serializer->serialize( $sense ) );
	}

	public function testReturnedDataContainsSenseId() {
		$serializer = new SenseSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$sense = NewSense::havingId( 'S3' )->build();

		$data = $serializer->serialize( $sense );

		$this->assertEquals( 'L1-S3', $data['id'] );
	}

	public function testReturnedDataContainsGlosses() {
		$glossListSerializer = $this->createMock( Serializer::class );
		$glossListSerializer->method( 'serialize' )
			->willReturn( 'REPRESENTATION DATA' );

		$serializer = new SenseSerializer(
			$glossListSerializer,
			$this->createMock( Serializer::class )
		);

		$sense = NewSense::havingId( 'S3' )->build();

		$data = $serializer->serialize( $sense );

		$this->assertEquals( 'REPRESENTATION DATA', $data['glosses'] );
	}

	public function testReturnedDataContainsStatements() {
		$statementSerializer = $this->createMock( Serializer::class );
		$statementSerializer->method( 'serialize' )
			->willReturn( 'STATEMENTS DATA' );

		$serializer = new SenseSerializer(
			$this->createMock( Serializer::class ),
			$statementSerializer
		);

		$sense = NewSense::havingId( 'S3' )->build();

		$data = $serializer->serialize( $sense );

		$this->assertEquals( 'STATEMENTS DATA', $data['claims'] );
	}

}

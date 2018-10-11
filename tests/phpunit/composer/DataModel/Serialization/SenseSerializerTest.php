<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Serializers\Serializer;
use Wikibase\Lexeme\Domain\DataModel\Serialization\SenseSerializer;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\DataModel\NewSense;

/**
 * @covers \Wikibase\Lexeme\Domain\DataModel\Serialization\SenseSerializer
 *
 * @license GPL-2.0-or-later
 */
class SenseSerializerTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGivenLexeme_isSerializerForReturnsFalse() {
		$serializer = new SenseSerializer(
			$this->getMock( Serializer::class ),
			$this->getMock( Serializer::class )
		);

		$lexeme = NewLexeme::create()->build();
		$this->assertFalse( $serializer->isSerializerFor( $lexeme ) );
	}

	public function testGivenSense_isSerializerForReturnsTrue() {
		$serializer = new SenseSerializer(
			$this->getMock( Serializer::class ),
			$this->getMock( Serializer::class )
		);

		$sense = NewSense::havingId( 'S1' )->build();
		$this->assertTrue( $serializer->isSerializerFor( $sense ) );
	}

	/**
	 * @expectedException \Serializers\Exceptions\UnsupportedObjectException
	 */
	public function testGivenLexeme_serializeThrowsException() {
		$serializer = new SenseSerializer(
			$this->getMock( Serializer::class ),
			$this->getMock( Serializer::class )
		);

		$lexeme = NewLexeme::create()->build();
		$serializer->serialize( $lexeme );
	}

	public function testGivenSense_serializeReturnsSerializedData() {
		$serializer = new SenseSerializer(
			$this->getMock( Serializer::class ),
			$this->getMock( Serializer::class )
		);

		$sense = NewSense::havingId( 'S1' )->build();
		$this->assertInternalType( 'array', $serializer->serialize( $sense ) );
	}

	public function testReturnedDataContainsSenseId() {
		$serializer = new SenseSerializer(
			$this->getMock( Serializer::class ),
			$this->getMock( Serializer::class )
		);

		$sense = NewSense::havingId( 'S3' )->build();

		$data = $serializer->serialize( $sense );

		$this->assertEquals( 'L1-S3', $data['id'] );
	}

	public function testReturnedDataContainsGlosses() {
		$glossListSerializer = $this->getMock( Serializer::class );
		$glossListSerializer->method( 'serialize' )
			->willReturn( 'REPRESENTATION DATA' );

		$serializer = new SenseSerializer(
			$glossListSerializer,
			$this->getMock( Serializer::class )
		);

		$sense = NewSense::havingId( 'S3' )->build();

		$data = $serializer->serialize( $sense );

		$this->assertEquals( 'REPRESENTATION DATA', $data['glosses'] );
	}

	public function testReturnedDataContainsStatements() {
		$statementSerializer = $this->getMock( Serializer::class );
		$statementSerializer->method( 'serialize' )
			->willReturn( 'STATEMENTS DATA' );

		$serializer = new SenseSerializer(
			$this->getMock( Serializer::class ),
			$statementSerializer
		);

		$sense = NewSense::havingId( 'S3' )->build();

		$data = $serializer->serialize( $sense );

		$this->assertEquals( 'STATEMENTS DATA', $data['claims'] );
	}

}

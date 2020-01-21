<?php

namespace Wikibase\Lexeme\Tests\DataModel\Serialization;

use PHPUnit\Framework\TestCase;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\Lexeme\Serialization\FormSerializer;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;

/**
 * @covers \Wikibase\Lexeme\Serialization\FormSerializer
 *
 * @license GPL-2.0-or-later
 */
class FormSerializerTest extends TestCase {

	public function testGivenLexeme_isSerializerForReturnsFalse() {
		$serializer = new FormSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$this->assertFalse( $serializer->isSerializerFor( NewLexeme::create()->build() ) );
	}

	public function testGivenForm_isSerializerForReturnsTrue() {
		$serializer = new FormSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$this->assertTrue( $serializer->isSerializerFor( NewForm::any()->build() ) );
	}

	public function testGivenLexeme_serializeThrowsException() {
		$serializer = new FormSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$this->expectException( UnsupportedObjectException::class );
		$serializer->serialize( NewLexeme::create()->build() );
	}

	public function testGivenForm_serializeReturnsSerializedData() {
		$serializer = new FormSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$this->assertIsArray( $serializer->serialize( NewForm::any()->build() ) );
	}

	public function testReturnedDataContainsFormId() {
		$serializer = new FormSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$form = NewForm::havingLexeme( 'L1' )->andId( 'F3' )->build();

		$data = $serializer->serialize( $form );

		$this->assertEquals( 'L1-F3', $data['id'] );
	}

	public function testReturnedDataContainsRepresentations() {
		$representationListSerializer = $this->createMock( Serializer::class );
		$representationListSerializer->method( 'serialize' )
			->willReturn( 'REPRESENTATION DATA' );

		$serializer = new FormSerializer(
			$representationListSerializer,
			$this->createMock( Serializer::class )
		);

		$form = NewForm::any()->build();

		$data = $serializer->serialize( $form );

		$this->assertEquals( 'REPRESENTATION DATA', $data['representations'] );
	}

	public function testReturnedDataContainsGrammaticalFeatures() {
		$serializer = new FormSerializer(
			$this->createMock( Serializer::class ),
			$this->createMock( Serializer::class )
		);

		$form = NewForm::havingGrammaticalFeature( 'Q100' )->andGrammaticalFeature( 'Q500' )->build();

		$data = $serializer->serialize( $form );

		$this->assertEquals( [ 'Q100', 'Q500' ], $data['grammaticalFeatures'] );
	}

	public function testReturnedDataContainsStatements() {
		$statementSerializer = $this->createMock( Serializer::class );
		$statementSerializer->method( 'serialize' )
			->willReturn( 'STATEMENTS DATA' );

		$serializer = new FormSerializer(
			$this->createMock( Serializer::class ),
			$statementSerializer
		);

		$form = NewForm::any()->build();

		$data = $serializer->serialize( $form );

		$this->assertEquals( 'STATEMENTS DATA', $data['claims'] );
	}

}

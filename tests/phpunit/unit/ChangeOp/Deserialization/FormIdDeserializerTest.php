<?php

namespace Wikibase\Lexeme\Tests\Unit\ChangeOp\Deserialization;

use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotFormId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer
 *
 * @license GPL-2.0-or-later
 */
class FormIdDeserializerTest extends MediaWikiUnitTestCase {

	public function testDeserializeValidFormId_returnsFormId() {
		$formId = new FormId( 'L1-F1' );

		$entityIdParser = $this->createMock( EntityIdParser::class );
		$entityIdParser->method( 'parse' )
			->with( 'L1-F1' )
			->willReturn( $formId );

		$context = $this->createMock( ValidationContext::class );
		$context
			->expects( $this->never() )
			->method( 'addViolation' );

		$deserializer = new FormIdDeserializer( $entityIdParser );

		$this->assertSame( $formId, $deserializer->deserialize( 'L1-F1', $context ) );
	}

	public function testDeserializeNotValidFormId_returnsNullAndContextHasViolation() {
		$entityIdParser = $this->createMock( EntityIdParser::class );
		$entityIdParser->method( 'parse' )
			->with( 'somesome' )
			->willThrowException( new EntityIdParsingException( 'so sad' ) );

		$context = $this->createMock( ValidationContext::class );
		$context
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotFormId( 'somesome' ) );

		$deserializer = new FormIdDeserializer( $entityIdParser );

		$this->assertNull( $deserializer->deserialize( 'somesome', $context ) );
	}

	public function testDeserializeNonFormReferencingFormId_returnsNullAndContextHasViolation() {
		$formId = $this->createMock( FormId::class );
		$formId->method( 'getEntityType' )
			->willReturn( 'weird' );

		$entityIdParser = $this->createMock( EntityIdParser::class );
		$entityIdParser->method( 'parse' )
			->with( 'L1-F1' )
			->willReturn( $formId );

		$context = $this->createMock( ValidationContext::class );
		$context
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotFormId( 'L1-F1' ) );

		$deserializer = new FormIdDeserializer( $entityIdParser );

		$this->assertNull( $deserializer->deserialize( 'L1-F1', $context ) );
	}

}

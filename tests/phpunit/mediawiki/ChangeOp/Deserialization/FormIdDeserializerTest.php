<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lexeme\MediaWiki\Api\Error\ParameterIsNotFormId;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\FormIdDeserializer
 *
 * @license GPL-2.0-or-later
 */
class FormIdDeserializerTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testDeserializeValidFormId_returnsFormId() {
		$formId = new FormId( 'L1-F1' );

		$entityIdParser = $this->getMock( EntityIdParser::class );
		$entityIdParser
			->method( 'parse' )
			->with( 'L1-F1' )
			->willReturn( $formId );

		$context = $this->getContextSpy();
		$context
			->expects( $this->never() )
			->method( 'addViolation' );

		$deserializer = new FormIdDeserializer( $entityIdParser );

		$this->assertSame( $formId, $deserializer->deserialize( 'L1-F1', $context ) );
	}

	public function testDeserializeNotValidFormId_returnsNullAndContextHasViolation() {
		$entityIdParser = $this->getMock( EntityIdParser::class );
		$entityIdParser
			->method( 'parse' )
			->with( 'somesome' )
			->willThrowException( new EntityIdParsingException( 'so sad' ) );

		$context = $this->getContextSpy();
		$context
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotFormId( 'somesome' ) );

		$deserializer = new FormIdDeserializer( $entityIdParser );

		$this->assertNull( $deserializer->deserialize( 'somesome', $context ) );
	}

	public function testDeserializeNonFormReferencingFormId_returnsNullAndContextHasViolation() {
		$formId = $this->getMockBuilder( FormId::class )
			->disableOriginalConstructor()
			->getMock();
		$formId
			->method( 'getEntityType' )
			->willReturn( 'weird' );

		$entityIdParser = $this->getMock( EntityIdParser::class );
		$entityIdParser
			->method( 'parse' )
			->with( 'L1-F1' )
			->willReturn( $formId );

		$context = $this->getContextSpy();
		$context
			->expects( $this->once() )
			->method( 'addViolation' )
			->with( new ParameterIsNotFormId( 'L1-F1' ) );

		$deserializer = new FormIdDeserializer( $entityIdParser );

		$this->assertNull( $deserializer->deserialize( 'L1-F1', $context ) );
	}

	private function getContextSpy() {
		return $this
			->getMockBuilder( ValidationContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

}

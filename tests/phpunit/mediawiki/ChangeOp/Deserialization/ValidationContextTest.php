<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\ChangeOp\Deserialization;

use ApiMessage;
use ApiUsageException;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\MediaWiki\Api\Error\ApiError;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @covers \Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext
 *
 * @license GPL-2.0-or-later
 */
class ValidationContextTest extends TestCase {

	public function testAddingContextLevels_buildsNestedTree() {
		$root = ValidationContext::create( 'data' );
		$level1 = $root->at( 'lorem' );
		$level2 = $level1->at( 'ipsum' );

		$this->assertInstanceOf( ValidationContext::class, $root );
		$this->assertInstanceOf( ValidationContext::class, $level1 );
		$this->assertNotSame( $root, $level1 );
		$this->assertInstanceOf( ValidationContext::class, $level2 );
		$this->assertNotSame( $level1, $level2 );
	}

	public function testAddingViolation_hasItConvertedToException() {
		$context = ValidationContext::create( 'data' )
			->at( 'representations' )
			->at( 'de' );

		$violation = $this->createMock( ApiError::class );
		$violation->method( 'asApiMessage' )
			->with( 'data', [ 'representations', 'de' ] )
			->willReturn( new ApiMessage( 'hello', 'world' ) );
		try {
			$context->addViolation( $violation );
			$this->fail( 'ApiUsageException was not thrown' );
		} catch ( ApiUsageException $exception ) {
			/** @var ApiMessage $message */
			$message = $exception->getMessageObject();

			$this->assertInstanceOf( ApiMessage::class, $message );

			$this->assertSame( 'hello', $message->getKey() );
			$this->assertSame( 'world', $message->getApiCode() );
			$this->assertSame( $message->getParams(), [] );
			$this->assertSame( $message->getApiData(), [
				'parameterName' => 'data',
				'fieldPath' => [ 'representations', 'de' ]
			] );
		}
	}

}

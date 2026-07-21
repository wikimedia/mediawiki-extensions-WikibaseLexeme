<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\RestApi;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\MediaWiki\RestApi\ResponseFactory;

/**
 * @covers \Wikibase\Lexeme\MediaWiki\RestApi\ResponseFactory
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0-or-later
 */
class ResponseFactoryTest extends TestCase {

	public function testNewErrorResponseFromException(): void {
		$httpStatus = 404;
		$errorCode = 'lexeme-not-found';
		$errorMessage = 'testNewErrorResponseFromException error message';

		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			new UseCaseError( $errorCode, $errorMessage )
		);

		$this->assertJsonStringEqualsJsonString(
			"{ \"code\": \"{$errorCode}\", \"message\": \"{$errorMessage}\" }",
			$httpResponse->getBody()->getContents()
		);
		$this->assertSame( $httpStatus, $httpResponse->getStatusCode() );
	}

	public function testGivenErrorCodeNotAssignedStatusCode_throwLogicException(): void {
		$this->expectException( LogicException::class );

		( new ResponseFactory() )->newErrorResponseFromException(
			new UseCaseError( 'unknown-code', 'should throw a logic exception' )
		);
	}

}

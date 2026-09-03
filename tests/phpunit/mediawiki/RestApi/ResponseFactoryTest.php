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

	public function testNewSuccessResponse(): void {
		$body = '{ "id": "L1" }';

		$httpResponse = ( new ResponseFactory() )->newSuccessResponse( $body, 123, '20240101000000' );

		$this->assertSame( 200, $httpResponse->getStatusCode() );
		$this->assertSame( 'application/json', $httpResponse->getHeaderLine( 'Content-Type' ) );
		$this->assertSame( 'Mon, 01 Jan 2024 00:00:00 GMT', $httpResponse->getHeaderLine( 'Last-Modified' ) );
		$this->assertSame( '"123"', $httpResponse->getHeaderLine( 'ETag' ) );
		$this->assertSame( $body, $httpResponse->getBody()->getContents() );
	}

	public function testNewSuccessResponse_withCustomStatus(): void {
		$httpResponse = ( new ResponseFactory() )->newSuccessResponse( '{}', 123, '20240101000000', 201 );

		$this->assertSame( 201, $httpResponse->getStatusCode() );
	}

	public function testNewErrorResponseFromException(): void {
		$httpStatus = 404;
		$errorCode = 'resource-not-found';
		$errorMessage = 'testNewErrorResponseFromException error message';

		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			new UseCaseError( $errorCode, $errorMessage, [ 'resource_type' => 'lexeme' ] )
		);

		$this->assertJsonStringEqualsJsonString(
			json_encode( [
				'code' => $errorCode,
				'message' => $errorMessage,
				'context' => [ 'resource_type' => 'lexeme' ],
			] ),
			$httpResponse->getBody()->getContents()
		);
		$this->assertSame( $httpStatus, $httpResponse->getStatusCode() );
	}

	public function testNewErrorResponseFromException_includesContext(): void {
		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			UseCaseError::newInvalidPathParameter( 'lexeme_id' )
		);

		$this->assertSame( 400, $httpResponse->getStatusCode() );
		$this->assertJsonStringEqualsJsonString(
			'{ "code": "invalid-path-parameter",'
				. ' "message": "Invalid path parameter: \'lexeme_id\'",'
				. ' "context": { "parameter": "lexeme_id" } }',
			$httpResponse->getBody()->getContents()
		);
	}

	public function testGivenErrorCodeNotAssignedStatusCode_throwLogicException(): void {
		$this->expectException( LogicException::class );

		( new ResponseFactory() )->newErrorResponseFromException(
			new UseCaseError( 'unknown-code', 'should throw a logic exception' )
		);
	}

	public function testNewErrorResponseFromException_missingField(): void {
		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			UseCaseError::newMissingField( '/lexeme', 'lemmas' )
		);

		$this->assertSame( 400, $httpResponse->getStatusCode() );
		$this->assertJsonStringEqualsJsonString(
			'{ "code": "missing-field", "message": "Required field missing",'
				. ' "context": { "path": "/lexeme", "field": "lemmas" } }',
			$httpResponse->getBody()->getContents()
		);
	}

	public function testNewErrorResponseFromException_invalidKey(): void {
		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			UseCaseError::newInvalidKey( '/lexeme/lemmas', 'xyz' )
		);

		$this->assertSame( 400, $httpResponse->getStatusCode() );
		$this->assertJsonStringEqualsJsonString(
			'{ "code": "invalid-key", "message": "Invalid key \'xyz\' in \'/lexeme/lemmas\'",'
				. ' "context": { "path": "/lexeme/lemmas", "key": "xyz" } }',
			$httpResponse->getBody()->getContents()
		);
	}

	public function testNewErrorResponseFromException_valueTooLong(): void {
		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			UseCaseError::newValueTooLong( '/lexeme/lemmas/en', 1000 )
		);

		$this->assertSame( 400, $httpResponse->getStatusCode() );
		$this->assertJsonStringEqualsJsonString(
			'{ "code": "value-too-long", "message": "The input value is too long",'
				. ' "context": { "path": "/lexeme/lemmas/en", "limit": 1000 } }',
			$httpResponse->getBody()->getContents()
		);
	}

	public function testNewErrorResponseFromException_invalidValue(): void {
		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			UseCaseError::newInvalidValue( '/lexeme/lemmas' )
		);

		$this->assertSame( 400, $httpResponse->getStatusCode() );
		$this->assertJsonStringEqualsJsonString(
			'{ "code": "invalid-value", "message": "Invalid value at \'/lexeme/lemmas\'",'
				. ' "context": { "path": "/lexeme/lemmas" } }',
			$httpResponse->getBody()->getContents()
		);
	}

	public function testNewErrorResponseFromException_permissionDenied(): void {
		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED )
		);

		$this->assertSame( 403, $httpResponse->getStatusCode() );
		$this->assertJsonStringEqualsJsonString(
			'{ "code": "permission-denied", "message": "Access to resource is denied",'
				. ' "context": { "denial_reason": "blocked-user" } }',
			$httpResponse->getBody()->getContents()
		);
	}

	public function testNewErrorResponseFromException_permissionDeniedUnknownReason_respondsLikeTheFramework(): void {
		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				'You have no permission to create a lexeme'
			)
		);

		$this->assertSame( 403, $httpResponse->getStatusCode() );
		$this->assertSame( 'application/json', $httpResponse->getHeaderLine( 'Content-Type' ) );
		$this->assertJsonStringEqualsJsonString(
			'{ "error": "rest-write-denied", "httpCode": 403, "httpReason": "Forbidden" }',
			$httpResponse->getBody()->getContents()
		);
	}

}

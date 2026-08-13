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

}

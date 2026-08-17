<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use LogicException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\StringStream;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use Wikimedia\Timestamp\TimestampFormat as TS;

/**
 * @license GPL-2.0-or-later
 */
class ResponseFactory {

	private const array HTTP_STATUS_LOOKUP_TABLE = [
		// 400 errors:
		UseCaseError::INVALID_PATH_PARAMETER => 400,
		UseCaseError::MISSING_FIELD => 400,
		UseCaseError::INVALID_VALUE => 400,
		// 404 errors:
		UseCaseError::LEXEME_NOT_FOUND => 404,
	];

	public function newSuccessResponse(
		string $body,
		int $revId,
		string $lastModified,
		int $statusCode = 200,
	): Response {
		$httpResponse = new Response();
		$httpResponse->setStatus( $statusCode );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Last-Modified', ConvertibleTimestamp::convert( TS::RFC2822, $lastModified ) );
		$httpResponse->setHeader( 'ETag', "\"$revId\"" );
		$httpResponse->setBody( new StringStream( $body ) );

		return $httpResponse;
	}

	public function newErrorResponseFromException( UseCaseError $e ): Response {
		return $this->newErrorResponse( $e->errorCode, $e->errorMessage, $e->context );
	}

	private function newErrorResponse( string $code, string $message, array $context = [] ): Response {
		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setStatus( $this->lookupHttpStatus( $code ) );
		$httpResponse->setBody(
			new StringStream( json_encode(
					// array_filter drops 'context' from the body when it is empty
					array_filter( [ 'code' => $code, 'message' => $message, 'context' => $context ] ),
					JSON_UNESCAPED_SLASHES )
			) );

		return $httpResponse;
	}

	private function lookupHttpStatus( string $errorCode ): int {
		if ( !array_key_exists( $errorCode, self::HTTP_STATUS_LOOKUP_TABLE ) ) {
			throw new LogicException( "Error code '$errorCode' not found in lookup table" );
		}
		return self::HTTP_STATUS_LOOKUP_TABLE[$errorCode];
	}

}

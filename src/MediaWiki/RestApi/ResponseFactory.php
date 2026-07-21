<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use LogicException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\StringStream;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ResponseFactory {

	private const array HTTP_STATUS_LOOKUP_TABLE = [
		// 404 errors:
		UseCaseError::LEXEME_NOT_FOUND => 404,
	];

	public function newErrorResponseFromException( UseCaseError $e ): Response {
		return $this->newErrorResponse( $e->errorCode, $e->errorMessage );
	}

	private function newErrorResponse( string $code, string $message ): Response {
		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setStatus( $this->lookupHttpStatus( $code ) );
		$httpResponse->setBody(
			new StringStream( json_encode(
					[ 'code' => $code, 'message' => $message ],
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

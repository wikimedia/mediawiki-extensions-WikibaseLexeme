<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexeme;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeRequest;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeResponse;
use Wikibase\Lexeme\Presentation\RestSerialization\LemmasSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use Wikimedia\Timestamp\TimestampFormat as TS;

/**
 * @license GPL-2.0-or-later
 */
class GetLexemeRouteHandler extends SimpleHandler {

	public const LEXEME_ID_PATH_PARAM = 'lexeme_id';

	public function __construct(
		private GetLexeme $getLexeme,
		private MiddlewareHandler $middlewareHandler,
		private LemmasSerializer $lemmasSerializer
	) {
	}

	public static function factory(): Handler {
		return new self(
			WikibaseLexemeServices::getGetLexeme(),
			new MiddlewareHandler( [
				WikibaseLexemeServices::getUnexpectedErrorHandlerMiddleware(),
			] ),
			new LemmasSerializer()
		);
	}

	public function run( string $lexemeId ): Response {
		return $this->middlewareHandler->run( $this, fn () => $this->runUseCase( $lexemeId ) );
	}

	public function runUseCase( string $lexemeId ): Response {
		return $this->newSuccessHttpResponse(
			$this->getLexeme->execute( new GetLexemeRequest( $lexemeId ) )
		);
	}

	private function newSuccessHttpResponse( GetLexemeResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader(
			'Last-Modified',
			ConvertibleTimestamp::convert( TS::RFC2822, $useCaseResponse->lastModified )
		);
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->revisionId}\"" );
		$httpResponse->setBody( new StringStream(
			json_encode(
				[
					'id' => $useCaseResponse->lexeme->id->getSerialization(),
					'lemmas' => $this->lemmasSerializer->serialize( $useCaseResponse->lexeme->lemmas ),
				],
				JSON_UNESCAPED_SLASHES
			)
		) );

		return $httpResponse;
	}

	public function getParamSettings(): array {
		return [
			self::LEXEME_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	public function needsWriteAccess(): bool {
		return false;
	}

}

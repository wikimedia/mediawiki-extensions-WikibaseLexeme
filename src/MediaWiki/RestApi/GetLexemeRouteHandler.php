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
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\GlossesSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\LemmasSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\SensesSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\Domains\Statements\Application\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\ReferenceSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementSerializer;
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
		private LexemeSerializer $lexemeSerializer,
		private ResponseFactory $responseFactory,
	) {
	}

	public static function factory(): Handler {
		$propertyValuePairSerializer = new PropertyValuePairSerializer();
		$statementListSerializer = new StatementListSerializer(
			new StatementSerializer(
				$propertyValuePairSerializer,
				new ReferenceSerializer( $propertyValuePairSerializer )
			)
		);

		return new self(
			WikibaseLexemeServices::getGetLexeme(),
			new MiddlewareHandler( [
				WikibaseLexemeServices::getUnexpectedErrorHandlerMiddleware(),
			] ),
			new LexemeSerializer(
				new LemmasSerializer(),
				$statementListSerializer,
				new SensesSerializer( new GlossesSerializer(), $statementListSerializer ),
			),
			new ResponseFactory(),
		);
	}

	public function run( string $lexemeId ): Response {
		return $this->middlewareHandler->run( $this, fn () => $this->runUseCase( $lexemeId ) );
	}

	public function runUseCase( string $lexemeId ): Response {
		try {
			return $this->newSuccessHttpResponse(
				$this->getLexeme->execute( new GetLexemeRequest( $lexemeId ) )
			);
		} catch ( LexemeRedirect $e ) {
			return $this->newRedirectHttpResponse( $e );
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
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
				$this->lexemeSerializer->serialize( $useCaseResponse->lexeme ),
				JSON_UNESCAPED_SLASHES
			)
		) );

		return $httpResponse;
	}

	private function newRedirectHttpResponse( LexemeRedirect $e ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader(
			'Location',
			$this->getRouteUrl(
				[ self::LEXEME_ID_PATH_PARAM => $e->redirectTarget->getSerialization() ],
				$this->getRequest()->getQueryParams(),
			)
		);
		$httpResponse->setStatus( 308 );

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

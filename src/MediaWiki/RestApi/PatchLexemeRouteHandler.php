<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexeme;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeRequest;
use Wikibase\Lexeme\Interactors\PatchLexeme\PatchLexemeResponse;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchLexemeRouteHandler extends SimpleHandler {

	private const LEXEME_ID_PATH_PARAM = 'lexeme_id';
	private const PATCH_BODY_PARAM = 'patch';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	public function __construct(
		private PatchLexeme $patchLexeme,
		private MiddlewareHandler $middlewareHandler,
		private LexemeSerializer $lexemeSerializer,
		private ResponseFactory $responseFactory,
	) {
	}

	public static function factory(): Handler {
		return new self(
			WikibaseLexemeServices::getPatchLexeme(),
			new MiddlewareHandler( [
				WikibaseLexemeServices::getUnexpectedErrorHandlerMiddleware(),
			] ),
			WikibaseLexemeServices::getLexemeSerializer(),
			new ResponseFactory(),
		);
	}

	public function run( string $lexemeId ): Response {
		return $this->middlewareHandler->run( $this, fn () => $this->runUseCase( $lexemeId ) );
	}

	private function runUseCase( string $lexemeId ): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()

		try {
			return $this->newSuccessHttpResponse(
				$this->patchLexeme->execute(
					new PatchLexemeRequest(
						$lexemeId,
						$jsonBody[self::PATCH_BODY_PARAM],
						$jsonBody[self::TAGS_BODY_PARAM] ?? [],
						$jsonBody[self::BOT_BODY_PARAM] ?? false,
						$jsonBody[self::COMMENT_BODY_PARAM] ?? null,
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( PatchLexemeResponse $useCaseResponse ): Response {
		return $this->responseFactory->newSuccessResponse(
			json_encode(
				$this->lexemeSerializer->serialize( $useCaseResponse->lexeme ),
				JSON_UNESCAPED_SLASHES
			),
			$useCaseResponse->revisionId,
			$useCaseResponse->lastModified,
		);
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

	public function getSupportedRequestTypes(): array {
		return [ 'application/json', 'application/json-patch+json' ];
	}

	public function getBodyParamSettings(): array {
		return [
			self::PATCH_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::TAGS_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => [],
			],
			self::BOT_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => false,
			],
			self::COMMENT_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}

}

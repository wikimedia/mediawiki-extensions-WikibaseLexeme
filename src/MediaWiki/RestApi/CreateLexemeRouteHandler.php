<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexeme;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeRequest;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeResponse;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeRouteHandler extends SimpleHandler {

	public const LEXEME_BODY_PARAM = 'lexeme';

	public function __construct(
		private CreateLexeme $createLexeme,
		private MiddlewareHandler $middlewareHandler,
		private LexemeSerializer $lexemeSerializer,
		private ResponseFactory $responseFactory,
	) {
	}

	public static function factory(): Handler {
		return new self(
			WikibaseLexemeServices::getCreateLexeme(),
			new MiddlewareHandler( [
					WikibaseLexemeServices::getUnexpectedErrorHandlerMiddleware(),
					new UserAgentCheckMiddleware(),
				]
			),
			WikibaseLexemeServices::getLexemeSerializer(),
			new ResponseFactory(),
		);
	}

	public function run(): Response {
		return $this->middlewareHandler->run( $this, fn () => $this->runUseCase() );
	}

	public function runUseCase(): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()

		try {
			return $this->newSuccessHttpResponse(
				$this->createLexeme->execute(
					new CreateLexemeRequest( $jsonBody[self::LEXEME_BODY_PARAM] )
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( CreateLexemeResponse $useCaseResponse ): Response {
		return $this->responseFactory->newSuccessResponse(
			json_encode(
				$this->lexemeSerializer->serialize( $useCaseResponse->lexeme ),
				JSON_UNESCAPED_SLASHES
			),
			$useCaseResponse->revisionId,
			$useCaseResponse->lastModified,
			statusCode: 201,
		);
	}

	public function getBodyParamSettings(): array {
		return [
			self::LEXEME_BODY_PARAM => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

}

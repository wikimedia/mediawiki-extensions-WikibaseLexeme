<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use MediaWiki\HookContainer\HookRunner;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseInterface;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSense;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseRequest;
use Wikibase\Lexeme\Interactors\AddLexemeSense\AddLexemeSenseResponse;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\SenseSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware;
use Wikibase\Repo\RestApi\Middleware\AuthenticationMiddleware;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;
use Wikibase\Repo\RestApi\Middleware\UserAgentCheckMiddleware;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseRouteHandler extends SimpleHandler {

	private const LEXEME_ID_PATH_PARAM = 'lexeme_id';
	private const SENSE_BODY_PARAM = 'sense';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	public function __construct(
		private AddLexemeSense $addLexemeSense,
		private MiddlewareHandler $middlewareHandler,
		private SenseSerializer $senseSerializer,
		private ResponseFactory $responseFactory,
	) {
	}

	public static function factory(): Handler {
		return new self(
			WikibaseLexemeServices::getAddLexemeSense(),
			new MiddlewareHandler( [
				WikibaseLexemeServices::getUnexpectedErrorHandlerMiddleware(),
				new UserAgentCheckMiddleware(),
				new AuthenticationMiddleware( MediaWikiServices::getInstance()->getUserIdentityUtils() ),
				WikibaseLexemeServices::getPreconditionMiddlewareFactory()->newPreconditionMiddleware(
					fn ( RequestInterface $request ): string => $request->getPathParam( self::LEXEME_ID_PATH_PARAM )
				),
				new TempUserCreationResponseHeaderMiddleware(
					new HookRunner( MediaWikiServices::getInstance()->getHookContainer() )
				),
			] ),
			WikibaseLexemeServices::getSenseSerializer(),
			new ResponseFactory(),
		);
	}

	/**
	 * Preconditions are checked via {@link PreconditionMiddleware}
	 */
	public function checkPreconditions(): ?ResponseInterface {
		return null;
	}

	public function run( string $lexemeId ): Response {
		return $this->middlewareHandler->run( $this, fn () => $this->runUseCase( $lexemeId ) );
	}

	private function runUseCase( string $lexemeId ): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()
		$mwUser = $this->getAuthority()->getUser();

		try {
			return $this->newSuccessHttpResponse(
				$this->addLexemeSense->execute(
					new AddLexemeSenseRequest(
						$lexemeId,
						$jsonBody[self::SENSE_BODY_PARAM],
						$jsonBody[self::TAGS_BODY_PARAM] ?? [],
						$jsonBody[self::BOT_BODY_PARAM] ?? false,
						$jsonBody[self::COMMENT_BODY_PARAM] ?? null,
						$mwUser->isRegistered() ? $mwUser->getName() : null,
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	private function newSuccessHttpResponse( AddLexemeSenseResponse $useCaseResponse ): Response {
		return $this->responseFactory->newSuccessResponse(
			json_encode(
				$this->senseSerializer->serialize( $useCaseResponse->sense ),
				JSON_UNESCAPED_SLASHES
			),
			$useCaseResponse->revisionId,
			$useCaseResponse->lastModified,
			statusCode: 201,
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

	public function getBodyParamSettings(): array {
		return [
			self::SENSE_BODY_PARAM => [
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

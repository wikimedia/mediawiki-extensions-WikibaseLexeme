<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatement;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementRequest;
use Wikibase\Lexeme\Interactors\AddLexemeStatement\AddLexemeStatementResponse;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementSerializer;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementRouteHandler extends SimpleHandler {

	private const LEXEME_ID_PATH_PARAM = 'lexeme_id';
	private const STATEMENT_BODY_PARAM = 'statement';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	public function __construct(
		private AddLexemeStatement $addLexemeStatement,
		private StatementSerializer $statementSerializer,
		private ResponseFactory $responseFactory,
	) {
	}

	public static function factory(): Handler {
		return new self(
			WikibaseLexemeServices::getAddLexemeStatement(),
			WbCrud::getStatementSerializer(),
			new ResponseFactory(),
		);
	}

	public function run( string $lexemeId ): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()

		return $this->newSuccessHttpResponse(
			$this->addLexemeStatement->execute(
				new AddLexemeStatementRequest(
					$lexemeId,
					$jsonBody[self::STATEMENT_BODY_PARAM],
					$jsonBody[self::TAGS_BODY_PARAM] ?? [],
					$jsonBody[self::BOT_BODY_PARAM] ?? false,
					$jsonBody[self::COMMENT_BODY_PARAM] ?? null,
				)
			)
		);
	}

	private function newSuccessHttpResponse( AddLexemeStatementResponse $useCaseResponse ): Response {
		return $this->responseFactory->newSuccessResponse(
			json_encode(
				$this->statementSerializer->serialize( $useCaseResponse->statement ),
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
			self::STATEMENT_BODY_PARAM => [
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

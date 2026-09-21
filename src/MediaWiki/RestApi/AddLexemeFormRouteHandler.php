<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeForm;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormRequest;
use Wikibase\Lexeme\Interactors\AddLexemeForm\AddLexemeFormResponse;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\FormSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeFormRouteHandler extends SimpleHandler {

	private const LEXEME_ID_PATH_PARAM = 'lexeme_id';
	private const FORM_BODY_PARAM = 'form';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	public function __construct(
		private AddLexemeForm $addLexemeForm,
		private FormSerializer $formSerializer,
		private ResponseFactory $responseFactory,
	) {
	}

	public static function factory(): Handler {
		return new self(
			WikibaseLexemeServices::getAddLexemeForm(),
			WikibaseLexemeServices::getFormSerializer(),
			new ResponseFactory(),
		);
	}

	public function run( string $lexemeId ): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()

		try {
			return $this->newSuccessHttpResponse(
				$this->addLexemeForm->execute(
					new AddLexemeFormRequest(
						$lexemeId,
						$jsonBody[self::FORM_BODY_PARAM],
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

	private function newSuccessHttpResponse( AddLexemeFormResponse $useCaseResponse ): Response {
		return $this->responseFactory->newSuccessResponse(
			json_encode(
				$this->formSerializer->serialize( $useCaseResponse->form ),
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
			self::FORM_BODY_PARAM => [
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

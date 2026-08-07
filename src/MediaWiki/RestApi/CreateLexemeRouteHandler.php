<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexeme;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeRequest;
use Wikibase\Lexeme\Interactors\CreateLexeme\CreateLexemeResponse;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeRouteHandler extends SimpleHandler {

	public const LEXEME_BODY_PARAM = 'lexeme';

	public function __construct(
		private CreateLexeme $createLexeme,
		private LexemeSerializer $lexemeSerializer,
	) {
	}

	public static function factory(): Handler {
		return new self(
			WikibaseLexemeServices::getCreateLexeme(),
			WikibaseLexemeServices::getLexemeSerializer(),
		);
	}

	public function run(): Response {
		$jsonBody = $this->getValidatedBody();
		'@phan-var array $jsonBody'; // guaranteed to be an array per getBodyParamSettings()

		return $this->newSuccessHttpResponse(
			$this->createLexeme->execute(
				new CreateLexemeRequest( $jsonBody[self::LEXEME_BODY_PARAM] )
			)
		);
	}

	private function newSuccessHttpResponse( CreateLexemeResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( 201 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody( new StringStream(
			json_encode(
				$this->lexemeSerializer->serialize( $useCaseResponse->lexeme ),
				JSON_UNESCAPED_SLASHES
			)
		) );

		return $httpResponse;
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

<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\RestApi;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Lexeme\DataAccess\Store\EntityLookupLexemeRetriever;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexeme;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeRequest;
use Wikibase\Lexeme\Interactors\GetLexeme\GetLexemeResponse;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetLexemeRouteHandler extends SimpleHandler {

	public const LEXEME_ID_PATH_PARAM = 'lexeme_id';

	public function __construct(
		private GetLexeme $getLexeme
	) {
	}

	public static function factory(): Handler {
		return new self(
			new GetLexeme( new EntityLookupLexemeRetriever( WikibaseRepo::getEntityLookup() ) )
		);
	}

	public function run( string $lexemeId ): Response {
		return $this->newSuccessHttpResponse(
			$this->getLexeme->execute( new GetLexemeRequest( $lexemeId ) )
		);
	}

	private function newSuccessHttpResponse( GetLexemeResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody( new StringStream(
			json_encode(
				[
					'id' => $useCaseResponse->lexeme->id->getSerialization(),
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

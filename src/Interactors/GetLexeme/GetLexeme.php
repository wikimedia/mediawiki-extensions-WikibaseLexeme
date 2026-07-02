<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\GetLexeme;

use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetLexeme {

	public function __construct(
		private LexemeRetriever $lexemeRetriever
	) {
	}

	public function execute( GetLexemeRequest $request ): GetLexemeResponse {
		$lexeme = $this->lexemeRetriever->getLexeme( new LexemeId( $request->lexemeId ) );

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable TODO handle Lexeme not found
		return new GetLexemeResponse( $lexeme );
	}

}

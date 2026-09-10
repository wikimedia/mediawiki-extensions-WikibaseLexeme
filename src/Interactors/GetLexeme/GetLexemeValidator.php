<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\GetLexeme;

use LogicException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetLexemeValidator {

	private ?LexemeId $lexemeId = null;

	public function __construct( private LexemeIdValidator $lexemeIdValidator ) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( GetLexemeRequest $request ): void {
		$this->lexemeId = $this->lexemeIdValidator->validate( $request->lexemeId );
	}

	public function getValidatedLexemeId(): LexemeId {
		if ( $this->lexemeId === null ) {
			throw new LogicException( 'Must not call getValidatedLexemeId() before validate()' );
		}

		return $this->lexemeId;
	}

}

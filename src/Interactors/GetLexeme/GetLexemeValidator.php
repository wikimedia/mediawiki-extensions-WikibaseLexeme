<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\GetLexeme;

use InvalidArgumentException;
use LogicException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class GetLexemeValidator {

	private ?LexemeId $lexemeId = null;

	/**
	 * @throws UseCaseError
	 */
	public function validate( GetLexemeRequest $request ): void {
		try {
			$this->lexemeId = new LexemeId( $request->lexemeId );
		} catch ( InvalidArgumentException ) {
			throw UseCaseError::newInvalidPathParameter( 'lexeme_id' );
		}
	}

	public function getValidatedLexemeId(): LexemeId {
		if ( $this->lexemeId === null ) {
			throw new LogicException( 'Must not call getValidatedLexemeId() before validate()' );
		}

		return $this->lexemeId;
	}

}

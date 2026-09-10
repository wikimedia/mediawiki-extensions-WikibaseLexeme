<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\UseCaseRequestValidation;

use InvalidArgumentException;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class LexemeIdValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validate( string $lexemeId ): LexemeId {
		try {
			return new LexemeId( $lexemeId );
		} catch ( InvalidArgumentException ) {
			throw UseCaseError::newInvalidPathParameter( 'lexeme_id' );
		}
	}

}

<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\UseCaseRequestValidation;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\ItemExistenceChecker;

/**
 * @license GPL-2.0-or-later
 */
class ItemIdValidator {

	public function __construct( private readonly ItemExistenceChecker $itemExistenceChecker ) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateItemId( mixed $value, string $path ): ItemId {
		if ( !is_string( $value ) ) {
			throw UseCaseError::newInvalidValue( $path );
		}
		try {
			$itemId = new ItemId( $value );
		} catch ( InvalidArgumentException ) {
			throw UseCaseError::newInvalidValue( $path );
		}
		if ( !$this->itemExistenceChecker->exists( $itemId ) ) {
			throw UseCaseError::newReferencedResourceNotFound( $path );
		}

		return $itemId;
	}

}

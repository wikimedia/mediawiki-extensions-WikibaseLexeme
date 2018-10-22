<?php

namespace Wikibase\Lexeme\Domain\Merge\Validator;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class NoConflictingTermListValues {

	/**
	 * @param TermList $source
	 * @param TermList $target
	 * @return bool
	 */
	public function validate( TermList $source, TermList $target ) {
		foreach ( $source as $term ) {
			/** @var $term Term */
			if (
				$target->hasTermForLanguage( $term->getLanguageCode() )
				&&
				!$target->hasTerm( $term )
			) {
				return false;
			}
		}

		return true;
	}

}

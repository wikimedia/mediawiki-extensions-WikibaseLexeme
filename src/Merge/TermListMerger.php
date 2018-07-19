<?php

namespace Wikibase\Lexeme\Merge;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class TermListMerger {

	/**
	 * @param TermList $source
	 * @param TermList $target Will be modified by reference
	 */
	public function merge( TermList $source, TermList $target ) {
		foreach ( $source as $term ) {
			if ( $target->hasTerm( $term ) ) {
				continue;
			}

			$target->setTerm( $term );
		}
	}

}

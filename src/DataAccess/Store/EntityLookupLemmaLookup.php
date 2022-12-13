<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class EntityLookupLemmaLookup implements LemmaLookup {

	private EntityLookup $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	public function getLemmas( LexemeId $lexemeId ): TermList {
		try {
			$lexeme = $this->entityLookup->getEntity( $lexemeId );
		} catch ( UnresolvedEntityRedirectException $ex ) { // T228996
			// Regression catch.
			// When there's a double redirect in Lexemes (eg. L1 -> L2 -> L3)
			// then getting lemmas of L1 will fatal as the second redirect is
			// not handled by the lookup, and the exception bubbles up here.
			// Fatal was caused by that exception as it wasn't handled. Seen on
			// Special:RecentChanges and Special:WhatLinksHere pages.
			// Handled gracefully with this catch, by returning an empty list,
			// effectively displaying the lexeme by its ID instead.
			return new TermList();
		}

		if ( $lexeme === null ) {
			return new TermList();
		}

		/** @var Lexeme $lexeme */
		'@phan-var Lexeme $lexeme';
		return $lexeme->getLemmas();
	}
}

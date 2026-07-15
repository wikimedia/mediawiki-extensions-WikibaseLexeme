<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use ArrayObject;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class Lemmas extends ArrayObject {

	public function __construct( Lemma ...$lemmas ) {
		parent::__construct(
			array_combine(
				array_map( static fn ( Lemma $l ) => $l->languageCode, $lemmas ),
				$lemmas
			)
		);
	}

	public static function fromTermList( TermList $list ): self {
		$lemmas = [];
		foreach ( $list->getIterator() as $term ) {
			$lemmas[] = Lemma::fromTerm( $term );
		}
		return new self( ...$lemmas );
	}

}

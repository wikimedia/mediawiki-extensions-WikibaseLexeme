<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use ArrayObject;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class Glosses extends ArrayObject {

	public function __construct( Gloss ...$glosses ) {
		parent::__construct(
			array_combine(
				array_map( static fn ( Gloss $g ) => $g->languageCode, $glosses ),
				$glosses
			)
		);
	}

	public static function fromTermList( TermList $list ): self {
		$glosses = [];
		foreach ( $list->getIterator() as $term ) {
			$glosses[] = Gloss::fromTerm( $term );
		}
		return new self( ...$glosses );
	}

}

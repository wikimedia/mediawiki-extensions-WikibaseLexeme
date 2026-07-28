<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use ArrayObject;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class Representations extends ArrayObject {

	public function __construct( Representation ...$representations ) {
		parent::__construct(
			array_combine(
				array_map( static fn ( Representation $r ) => $r->languageCode, $representations ),
				$representations
			)
		);
	}

	public static function fromTermList( TermList $list ): self {
		$representations = [];
		foreach ( $list->getIterator() as $term ) {
			$representations[] = Representation::fromTerm( $term );
		}
		return new self( ...$representations );
	}

}

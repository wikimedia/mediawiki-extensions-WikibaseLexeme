<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use ArrayObject;

/**
 * @license GPL-2.0-or-later
 */
class Forms extends ArrayObject {

	public function __construct( Form ...$forms ) {
		parent::__construct(
			array_combine(
				array_map( static fn ( Form $f ) => $f->id->getSerialization(), $forms ),
				$forms
			)
		);
	}
}

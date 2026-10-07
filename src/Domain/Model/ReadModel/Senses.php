<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use ArrayObject;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class Senses extends ArrayObject {

	public function __construct( Sense ...$senses ) {
		parent::__construct(
			array_combine(
				array_map( static fn ( Sense $s ) => $s->id->getSerialization(), $senses ),
				$senses
			)
		);
	}

	public function getById( SenseId $senseId ): ?Sense {
		return $this[$senseId->getSerialization()] ?? null;
	}

}

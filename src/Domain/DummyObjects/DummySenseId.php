<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\DummyObjects;

use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * An ID for a BlankSense which has already been associated with a particular lexeme.
 *
 * @license GPL-2.0-or-later
 */
class DummySenseId extends SenseId {

	public function equals( $target ): bool {
		return $this->stemsFromNewlyCreatedSense( $target )
			|| parent::equals( $target );
	}

	/**
	 * @param mixed $target
	 * @return bool
	 */
	private function stemsFromNewlyCreatedSense( $target ): bool {
		return $target instanceof NullSenseId;
	}

}

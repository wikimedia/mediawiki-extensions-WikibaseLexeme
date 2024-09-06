<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\DummyObjects;

use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 */
class DummyFormId extends FormId {

	public function equals( $target ): bool {
		return $this->stemsFromNewlyCreatedForm( $target )
			|| parent::equals( $target );
	}

	/**
	 * @param mixed $target
	 * @return bool
	 */
	private function stemsFromNewlyCreatedForm( $target ): bool {
		return $target instanceof NullFormId;
	}

}

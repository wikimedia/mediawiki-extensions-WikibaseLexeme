<?php

namespace Wikibase\Lexeme\Domain\DummyObjects;

use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 */
class DummyFormId extends FormId {

	public function equals( $target ) {
		return $this->stemsFromNewlyCreatedForm( $target )
			|| parent::equals( $target );
	}

	/**
	 * @param mixed $target
	 * @return bool
	 */
	private function stemsFromNewlyCreatedForm( $target ) {
		return $target instanceof NullFormId;
	}

}

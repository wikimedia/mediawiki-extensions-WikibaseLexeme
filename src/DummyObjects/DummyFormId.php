<?php

namespace Wikibase\Lexeme\DummyObjects;

use Wikibase\Lexeme\Domain\DataModel\FormId;

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

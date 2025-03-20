<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\DummyObjects;

use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Sense;

/**
 * A sense that has not yet been assigned an ID.
 * Its gloss list starts out empty, but may later be populated.
 * It may also be associated with a particular lexeme.
 *
 * @license GPL-2.0-or-later
 */
class BlankSense extends Sense {

	public function __construct() {
		parent::__construct(
			new NullSenseId(),
			new TermList()
		);
	}

	/** @inheritDoc */
	public function setId( $id ): void {
		parent::setId( new DummySenseId( $id->getSerialization() ) );
	}

}

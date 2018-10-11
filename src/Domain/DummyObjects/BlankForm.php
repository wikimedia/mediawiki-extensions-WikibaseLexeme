<?php

namespace Wikibase\Lexeme\Domain\DummyObjects;

use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;

/**
 * @license GPL-2.0-or-later
 */
class BlankForm extends Form {

	public function __construct() {
		parent::__construct(
			new NullFormId(),
			new TermList(),
			[]
		);
	}

	public function setId( $id ) {
		parent::setId( new DummyFormId( $id->getSerialization() ) );
	}

}

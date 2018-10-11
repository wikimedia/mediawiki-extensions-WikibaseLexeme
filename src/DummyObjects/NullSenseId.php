<?php

namespace Wikibase\Lexeme\DummyObjects;

use LogicException;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * An ID for a BlankSense which has not yet been associated with any lexeme.
 *
 * @license GPL-2.0-or-later
 */
class NullSenseId extends SenseId {

	public function __construct() {
		$this->serialization = '';
		$this->localPart = '';
		$this->repositoryName = '';
	}

	public function getLexemeId() {
		throw new LogicException( 'Shall never be called' );
	}

	public function serialize() {
		throw new LogicException( 'Shall never be called' );
	}

	public function unserialize( $serialized ) {
		throw new LogicException( 'Shall never be called' );
	}

	public function equals( $target ) {
		return true;
	}

}

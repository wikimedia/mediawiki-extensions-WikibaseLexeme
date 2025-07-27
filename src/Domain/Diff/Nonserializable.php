<?php

declare( strict_types = 1 );

// @phan-file-suppress PhanPluginNeverReturnMethod

namespace Wikibase\Lexeme\Domain\Diff;

use LogicException;

/**
 * Private helper trait to make some diffs nonserializable.
 *
 * @license GPL-2.0-or-later
 */
trait Nonserializable {

	public function __serialize(): array {
		throw new LogicException( "serialize() is not implemented" );
	}

	public function serialize() {
		throw new LogicException( "serialize() is not implemented" );
	}

	public function __unserialize( $data ): void {
		throw new LogicException( "serialize() is not implemented" );
	}

	/** @param mixed $serialized */
	public function unserialize( $serialized ) {
		throw new LogicException( "unserialize() is not implemented" );
	}

}

<?php

namespace Wikibase\Lexeme\Domain\DummyObjects;

use LogicException;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 * @phan-file-suppress PhanPluginNeverReturnMethod
 */
class NullFormId extends FormId {

	public function __construct() {
		$this->serialization = '';
	}

	public function getLexemeId() {
		throw new LogicException( 'Shall never be called' );
	}

	public function __serialize(): array {
		throw new LogicException( 'Shall never be called' );
	}

	public function __unserialize( array $data ): void {
		throw new LogicException( 'Shall never be called' );
	}

	public function equals( $target ) {
		return true;
	}

}

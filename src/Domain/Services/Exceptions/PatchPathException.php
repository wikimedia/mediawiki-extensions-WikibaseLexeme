<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Services\Exceptions;

use Exception;

/**
 * Copy of {@link \Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\PatchPathException}
 *
 * @license GPL-2.0-or-later
 */
class PatchPathException extends Exception {

	public function __construct( string $message, private string $field, private int $opIndex ) {
		parent::__construct( $message );
	}

	public function getField(): string {
		return $this->field;
	}

	public function getOpIndex(): int {
		return $this->opIndex;
	}

}

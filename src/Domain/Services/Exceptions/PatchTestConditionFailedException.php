<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Services\Exceptions;

use Exception;

/**
 * Copy of {@link \Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\PatchTestConditionFailedException}
 *
 * @license GPL-2.0-or-later
 */
class PatchTestConditionFailedException extends Exception {

	public function __construct( string $message, private array $operation, private mixed $actualValue ) {
		parent::__construct( $message );
	}

	public function getOperation(): array {
		return $this->operation;
	}

	public function getActualValue(): mixed {
		return $this->actualValue;
	}

}

<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class User {

	private function __construct( private readonly ?string $username ) {
	}

	public static function newAnonymous(): self {
		return new self( null );
	}

	public static function withUsername( string $username ): self {
		return new self( $username );
	}

	/**
	 * @return string|null null for an anonymous user
	 */
	public function getUsername(): ?string {
		return $this->username;
	}

	public function isAnonymous(): bool {
		return $this->username === null;
	}

}

<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use RuntimeException;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
final class LatestLexemeRevisionMetadataResult {

	private ?int $revisionId = null;
	private ?string $revisionTimestamp = null;
	private ?LexemeId $redirectTarget = null;

	public static function concreteRevision( int $revisionId, string $revisionTimestamp ): self {
		$result = new self();
		$result->revisionId = $revisionId;
		$result->revisionTimestamp = $revisionTimestamp;

		return $result;
	}

	public static function redirect( LexemeId $redirectTarget ): self {
		$result = new self();
		$result->redirectTarget = $redirectTarget;

		return $result;
	}

	public static function lexemeNotFound(): self {
		return new self();
	}

	/**
	 * @throws RuntimeException if not a concrete revision result
	 */
	public function getRevisionId(): int {
		if ( !$this->revisionId ) {
			throw new RuntimeException( __METHOD__ . ' called on a result object that does not contain a revision.' );
		}

		return $this->revisionId;
	}

	/**
	 * @throws RuntimeException if not a concrete revision result
	 */
	public function getRevisionTimestamp(): string {
		if ( !$this->revisionTimestamp ) {
			throw new RuntimeException( __METHOD__ . ' called on a result object that does not contain a revision.' );
		}

		return $this->revisionTimestamp;
	}

	/**
	 * @throws RuntimeException if not a redirect result
	 */
	public function getRedirectTarget(): LexemeId {
		if ( !$this->redirectTarget ) {
			throw new RuntimeException( __METHOD__ . ' called on a result object that does not contain a redirect.' );
		}

		return $this->redirectTarget;
	}

	public function isRedirect(): bool {
		return $this->redirectTarget !== null;
	}

	public function lexemeExists(): bool {
		return $this->revisionId || $this->redirectTarget;
	}

}

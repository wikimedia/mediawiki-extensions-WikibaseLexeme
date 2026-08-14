<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class LexemeRevision {

	public function __construct(
		public readonly Lexeme $lexeme,
		public readonly int $revisionId,
		/** @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss' */
		public readonly string $lastModified,
	) {
	}

}

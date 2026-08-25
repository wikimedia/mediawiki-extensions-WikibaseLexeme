<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class EditMetadata {

	public function __construct(
		public readonly array $tags,
		public readonly bool $isBot,
		public readonly ?string $comment,
		public readonly EditSummaryAction $editSummaryAction,
	) {
	}

}

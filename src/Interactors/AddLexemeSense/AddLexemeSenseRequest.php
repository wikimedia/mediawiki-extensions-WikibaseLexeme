<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeSense;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseRequest {

	public function __construct(
		public readonly string $lexemeId,
		public readonly array $sense,
		public readonly array $editTags,
		public readonly bool $isBot,
		public readonly ?string $comment,
	) {
	}

}

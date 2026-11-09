<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\PatchLexeme;

/**
 * @license GPL-2.0-or-later
 */
class PatchLexemeRequest {

	public function __construct(
		public readonly string $lexemeId,
		public readonly array $patch,
		public readonly array $editTags,
		public readonly bool $isBot,
		public readonly ?string $comment,
		public readonly ?string $username,
	) {
	}

}

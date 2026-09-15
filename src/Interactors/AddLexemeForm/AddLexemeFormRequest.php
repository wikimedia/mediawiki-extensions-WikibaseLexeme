<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeForm;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeFormRequest {

	public function __construct(
		public readonly string $lexemeId,
		public readonly array $form,
		public readonly array $editTags,
		public readonly bool $isBot,
		public readonly ?string $comment,
	) {
	}

}

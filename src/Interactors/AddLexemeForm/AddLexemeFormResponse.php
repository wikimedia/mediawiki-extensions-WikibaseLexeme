<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeForm;

use Wikibase\Lexeme\Domain\Model\ReadModel\Form;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeFormResponse {

	public function __construct(
		public readonly Form $form,
		public readonly int $revisionId,
		public readonly string $lastModified,
	) {
	}

}

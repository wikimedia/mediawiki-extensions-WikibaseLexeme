<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface TagsRetriever {

	public function getAllowedTags(): array;

}

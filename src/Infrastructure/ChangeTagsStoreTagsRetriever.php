<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Infrastructure;

use MediaWiki\ChangeTags\ChangeTagsStore;
use Wikibase\Lexeme\Validation\TagsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class ChangeTagsStoreTagsRetriever implements TagsRetriever {

	public function __construct( private readonly ChangeTagsStore $changeTagsStore ) {
	}

	public function getAllowedTags(): array {
		return $this->changeTagsStore->listExplicitlyDefinedTags();
	}

}

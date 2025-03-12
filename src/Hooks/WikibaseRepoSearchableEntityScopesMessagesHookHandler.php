<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\Hooks\WikibaseRepoSearchableEntityScopesMessagesHook;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoSearchableEntityScopesMessagesHookHandler implements WikibaseRepoSearchableEntityScopesMessagesHook {

	public const LEXEME_MESSAGE_KEY = 'wikibase-scoped-search-lexeme-scope-name';

	public function onWikibaseRepoSearchableEntityScopesMessages( array &$messages ): void {
		if ( !array_key_exists( Lexeme::ENTITY_TYPE, $messages ) ) {
			$messages[Lexeme::ENTITY_TYPE] = self::LEXEME_MESSAGE_KEY;
		}
	}

}

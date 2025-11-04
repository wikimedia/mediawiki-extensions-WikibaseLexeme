<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use Wikibase\Repo\Hooks\WikibaseRepoWbui2025InitResourceDependenciesHook;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoWbui2025InitResourceDependenciesHookHandler implements
	WikibaseRepoWbui2025InitResourceDependenciesHook
{

	public function onWikibaseRepoWbui2025InitResourceDependenciesHook( array &$dependencies ): void {
		$dependencies[] = 'wikibaseLexeme.wbui2025.entityViewInit';
	}

}

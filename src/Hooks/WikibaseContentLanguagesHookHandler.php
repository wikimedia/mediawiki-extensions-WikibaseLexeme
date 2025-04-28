<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Hooks\WikibaseContentLanguagesHook;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseContentLanguagesHookHandler implements WikibaseContentLanguagesHook {

	private ContentLanguages $termLanguages;

	public function __construct( ContentLanguages $termLanguages ) {
		$this->termLanguages = $termLanguages;
	}

	public function onWikibaseContentLanguages( array &$contentLanguages ): void {
		$contentLanguages[ 'term-lexicographical' ] = $this->termLanguages;
	}
}

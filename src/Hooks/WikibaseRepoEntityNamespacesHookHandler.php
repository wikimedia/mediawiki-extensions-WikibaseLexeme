<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use MediaWiki\Config\Config;
use Wikibase\Repo\Hooks\WikibaseRepoEntityNamespacesHook;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoEntityNamespacesHookHandler implements WikibaseRepoEntityNamespacesHook {

	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public function onWikibaseRepoEntityNamespaces( array &$entityNamespaces ): void {
		if ( !$this->config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		// Setting the namespace to false disabled automatic registration.
		$entityNamespaces[ 'lexeme' ] = $this->config->get( 'LexemeNamespace' );
	}
}

<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use MediaWiki\Config\Config;
use Wikibase\Repo\Hooks\WikibaseRepoDataTypesHook;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoDataTypesHookHandler implements WikibaseRepoDataTypesHook {

	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Adds the definition of the data types related to lexeme to the definitions array
	 * Wikibase uses.
	 *
	 * @param array[] &$dataTypeDefinitions
	 * @see WikibaseLexeme.datatypes.php
	 *
	 * @note This is bootstrap code, it is executed for EVERY request. Avoid instantiating
	 * objects or loading classes here!
	 *
	 */
	public function onWikibaseRepoDataTypes( array &$dataTypeDefinitions ): void {
		if ( !$this->config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		$dataTypeDefinitions =
			array_merge( $dataTypeDefinitions,
				require __DIR__ . '/../../WikibaseLexeme.datatypes.php' );
	}
}

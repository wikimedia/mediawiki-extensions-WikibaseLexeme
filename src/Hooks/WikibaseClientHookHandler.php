<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use Wikibase\Client\Hooks\WikibaseClientDataTypesHook;
use Wikibase\Client\Hooks\WikibaseClientEntityTypesHook;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseClientHookHandler implements
	WikibaseClientDataTypesHook,
	WikibaseClientEntityTypesHook
{

	/**
	 * Adds the definition of the data types related to lexeme to the definitions array
	 * Wikibase uses.
	 *
	 * @see WikibaseLexeme.datatypes.client.php
	 *
	 * @param array[] &$dataTypeDefinitions
	 */
	public function onWikibaseClientDataTypes( array &$dataTypeDefinitions ): void {
		$dataTypeDefinitions = array_merge(
			$dataTypeDefinitions,
			require __DIR__ . '/../../WikibaseLexeme.datatypes.client.php'
		);
	}

	/**
	 * Adds the definition of the lexeme entity type to the definitions array Wikibase uses.
	 *
	 * @see WikibaseLexeme.entitytypes.php
	 *
	 * @note This is bootstrap code, it is executed for EVERY request. Avoid instantiating
	 * objects or loading classes here!
	 */
	public function onWikibaseClientEntityTypes( array &$entityTypeDefinitions ): void {
		$entityTypeDefinitions = array_merge(
			$entityTypeDefinitions,
			require __DIR__ . '/../../WikibaseLexeme.entitytypes.php'
		);
	}
}

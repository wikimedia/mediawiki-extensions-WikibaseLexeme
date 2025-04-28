<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use MediaWiki\MediaWikiServices;
use Wikibase\Lexeme\MediaWiki\ParserOutput\LexemeParserOutputUpdater;
use Wikibase\Repo\Hooks\WikibaseRepoEntityTypesHook;
use Wikibase\Repo\Hooks\WikibaseRepoOnParserOutputUpdaterConstructionHook;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoHookHandler implements
	WikibaseRepoEntityTypesHook,
	WikibaseRepoOnParserOutputUpdaterConstructionHook
{

	/**
	 * Adds the definition of the lexeme entity type to the definitions array Wikibase uses.
	 *
	 * @see WikibaseLexeme.entitytypes.php
	 * @see WikibaseLexeme.entitytypes.repo.php
	 */
	public function onWikibaseRepoEntityTypes( array &$entityTypeDefinitions ): void {
		// This hook runs as part of an early initialization service and is therefore not
		// allowed to pull in arbitrary service dependencies.
		// `MainConfig` is safe. However, it is not possible to specify this granularity in
		// the HookRunner and we need to get the service via the old method.
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( !$config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		$entityTypeDefinitions = array_merge(
			$entityTypeDefinitions,
			wfArrayPlus2d(
				require __DIR__ . '/../../WikibaseLexeme.entitytypes.repo.php',
				require __DIR__ . '/../../WikibaseLexeme.entitytypes.php'
			)
		);
	}

	public function onWikibaseRepoOnParserOutputUpdaterConstruction(
		StatementDataUpdater $statementUpdater, array &$entityUpdaters
	): void {
		$entityUpdaters[] = new LexemeParserOutputUpdater( $statementUpdater );
	}
}

<?php

namespace Wikibase\Lexeme;

use IContextSource;
use MediaWiki\MediaWikiServices;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lexeme\MediaWiki\Actions\InfoActionHookHandler;
use Wikibase\Lexeme\MediaWiki\ParserOutput\LexemeParserOutputUpdater;
use Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntityFormLibrary;
use Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntityLexemeLibrary;
use Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeEntitySenseLibrary;
use Wikibase\Lexeme\MediaWiki\Scribunto\Scribunto_LuaWikibaseLexemeLibrary;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\ParserOutput\CompositeStatementDataUpdater;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;

/**
 * MediaWiki hook handlers for the Wikibase Lexeme extension.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class WikibaseLexemeHooks {

	/**
	 * Hook to register the lexeme and other entity namespaces for EntityNamespaceLookup.
	 *
	 * @param int[] $entityNamespacesSetting
	 */
	public static function onWikibaseRepoEntityNamespaces( array &$entityNamespacesSetting ) {
		// XXX: ExtensionProcessor should define an extra config object for every extension.
		$config = MediaWikiServices::getInstance()->getMainConfig();

		if ( !$config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		// Setting the namespace to false disabled automatic registration.
		$entityNamespacesSetting['lexeme'] = $config->get( 'LexemeNamespace' );
	}

	/**
	 * Hook to register the default namespace names.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
	 */
	public static function onCanonicalNamespaces( array &$namespaces ) {
		// XXX: ExtensionProcessor should define an extra config object for every extension.
		$config = MediaWikiServices::getInstance()->getMainConfig();

		// Do not register lexeme namespaces when the repo is not enabled.
		if ( !WikibaseSettings::isRepoEnabled() || !$config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		// Setting the namespace to false disabled automatic registration.
		$lexemeNamespaceId = $config->get( 'LexemeNamespace' );
		if ( $lexemeNamespaceId !== false ) {
			Assert::parameter(
				is_int( $lexemeNamespaceId ) &&
					$lexemeNamespaceId >= 100 &&
					( $lexemeNamespaceId % 2 ) === 0,
				'$wgLexemeNamespace',
				'Namespace ID must be an even integer, at least 100'
			);
			$lexemeNamespaceName = 'Lexeme';
			$namespaces = self::registerNamespace(
				$namespaces,
				$lexemeNamespaceId,
				$lexemeNamespaceName
			);

			$talkNamespaceId = $lexemeNamespaceId + 1;
			$talkNamespaceName = $lexemeNamespaceName . '_talk';
			$namespaces = self::registerNamespace(
				$namespaces,
				$talkNamespaceId,
				$talkNamespaceName
			);
		}
	}

	/**
	 * Adds the definition of the lexeme entity type to the definitions array Wikibase uses.
	 *
	 * @see WikibaseLexeme.entitytypes.php
	 *
	 * @note This is bootstrap code, it is executed for EVERY request. Avoid instantiating
	 * objects or loading classes here!
	 *
	 * @param array[] $entityTypeDefinitions
	 */
	public static function onWikibaseClientEntityTypes( array &$entityTypeDefinitions ) {
		$entityTypeDefinitions = array_merge(
			$entityTypeDefinitions,
			require __DIR__ . '/../WikibaseLexeme.entitytypes.php'
		);
	}

	/**
	 * Adds the definition of the lexeme entity type to the definitions array Wikibase uses.
	 *
	 * @see WikibaseLexeme.entitytypes.php
	 * @see WikibaseLexeme.entitytypes.repo.php
	 *
	 * @param array[] $entityTypeDefinitions
	 */
	public static function onWikibaseRepoEntityTypes( array &$entityTypeDefinitions ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( !$config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		$entityTypeDefinitions = array_merge(
			$entityTypeDefinitions,
			wfArrayPlus2d(
				require __DIR__ . '/../WikibaseLexeme.entitytypes.repo.php',
				require __DIR__ . '/../WikibaseLexeme.entitytypes.php'
			)
		);
	}

	/**
	 * Adds the definition of the data types related to lexeme to the definitions array
	 * Wikibase uses.
	 *
	 * @see WikibaseLexeme.datatypes.php
	 *
	 * @note This is bootstrap code, it is executed for EVERY request. Avoid instantiating
	 * objects or loading classes here!
	 *
	 * @param array[] $dataTypeDefinitions
	 */
	public static function onWikibaseDataTypes( array &$dataTypeDefinitions ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( !$config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		$dataTypeDefinitions = array_merge(
			$dataTypeDefinitions,
			require __DIR__ . '/../WikibaseLexeme.datatypes.php'
		);
	}

	/**
	 * Adds the definition of the data types related to lexeme to the definitions array
	 * Wikibase uses.
	 *
	 * @see WikibaseLexeme.datatypes.client.php
	 *
	 * @param array[] $dataTypeDefinitions
	 */
	public static function onWikibaseClientDataTypes( array &$dataTypeDefinitions ) {
		$dataTypeDefinitions = array_merge(
			$dataTypeDefinitions,
			require __DIR__ . '/../WikibaseLexeme.datatypes.client.php'
		);
	}

	public static function onWikibaseContentLanguages( array &$contentLanguages ) {
		$contentLanguages['term-lexicographical'] = WikibaseLexemeServices::getTermLanguages();
	}

	/**
	 * @param string[] $namespaces
	 * @param int $namespaceId
	 * @param string $namespaceName
	 *
	 * @return string[]
	 * @throws \RuntimeException If namespace ID is already registered with another name
	 */
	private static function registerNamespace( array $namespaces, $namespaceId, $namespaceName ) {
		if (
			isset( $namespaces[$namespaceId] ) &&
			$namespaces[$namespaceId] !== $namespaceName
		) {
			throw new \RuntimeException(
				"Tried to register `$namespaceName` namespace with ID `$namespaceId`, " .
				"but ID was already occupied by `{$namespaces[$namespaceId]} namespace`"
			);
		}

		$namespaces[$namespaceId] = $namespaceName;

		return $namespaces;
	}

	public static function onParserOutputUpdaterConstruction(
		CompositeStatementDataUpdater $statementUpdater, array &$entityUpdaters
	) {
		$entityUpdaters[] = new LexemeParserOutputUpdater( $statementUpdater );
	}

	/**
	 * Adds the Wikis using the entity in action=info
	 *
	 * @param IContextSource $context
	 * @param array[] &$pageInfo
	 */
	public static function onInfoAction( IContextSource $context, array &$pageInfo ) {
		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		if ( !$config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		$namespaceChecker = WikibaseRepo::getEntityNamespaceLookup();
		$infoActionHookHandler = new InfoActionHookHandler(
			$namespaceChecker,
			WikibaseRepo::getEntityIdLookup(),
			$services->getPageProps(),
			$context
		);

		$pageInfo = $infoActionHookHandler->handle( $context, $pageInfo );
	}

	public static function onScribuntoExternalLibraries( $engine, array &$extraLibraries ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( !$config->get( 'LexemeEnableDataTransclusion' ) ) {
			return;
		}
		$clientSettings = WikibaseClient::getSettings();
		if ( !$clientSettings->getSetting( 'allowDataTransclusion' ) ) {
			return;
		}

		if ( $engine == 'lua' ) {
			$extraLibraries['mw.wikibase.lexeme']
				= Scribunto_LuaWikibaseLexemeLibrary::class;
			$extraLibraries['mw.wikibase.lexeme.entity.lexeme'] = [
				'class' => Scribunto_LuaWikibaseLexemeEntityLexemeLibrary::class,
				'deferLoad' => true,
			];
			$extraLibraries['mw.wikibase.lexeme.entity.form'] = [
				'class' => Scribunto_LuaWikibaseLexemeEntityFormLibrary::class,
				'deferLoad' => true,
			];
			$extraLibraries['mw.wikibase.lexeme.entity.sense'] = [
				'class' => Scribunto_LuaWikibaseLexemeEntitySenseLibrary::class,
				'deferLoad' => true,
			];
		}
	}

	public static function getLexemeViewLanguages(): array {
		return [
			'lexemeTermLanguages' => MediaWikiServices::getInstance()
				->getService( 'WikibaseLexemeTermLanguages' )->getLanguages(),
		];
	}

}

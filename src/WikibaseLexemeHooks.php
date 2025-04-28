<?php

namespace Wikibase\Lexeme;

use MediaWiki\Context\IContextSource;
use MediaWiki\Hook\CanonicalNamespacesHook;
use MediaWiki\Hook\InfoActionHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\MediaWikiServices;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lexeme\Maintenance\FixPagePropsSortkey;
use Wikibase\Lexeme\MediaWiki\Actions\InfoActionHookHandler;
use Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntityFormLibrary;
use Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntityLexemeLibrary;
use Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeEntitySenseLibrary;
use Wikibase\Lexeme\MediaWiki\Scribunto\WikibaseLexemeLibrary;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;

/**
 * MediaWiki hook handlers for the Wikibase Lexeme extension.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class WikibaseLexemeHooks implements
	InfoActionHook,
	CanonicalNamespacesHook,
	LoadExtensionSchemaUpdatesHook
{

	/**
	 * Hook to register the default namespace names.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
	 *
	 * @param string[] &$namespaces
	 */
	public function onCanonicalNamespaces( &$namespaces ) {
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

	/**
	 * Adds the Wikis using the entity in action=info
	 *
	 * @param IContextSource $context
	 * @param array[] &$pageInfo
	 */
	public function onInfoAction( $context, &$pageInfo ) {
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

	/** @inheritDoc */
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
				= WikibaseLexemeLibrary::class;
			$extraLibraries['mw.wikibase.lexeme.entity.lexeme'] = [
				'class' => WikibaseLexemeEntityLexemeLibrary::class,
				'deferLoad' => true,
			];
			$extraLibraries['mw.wikibase.lexeme.entity.form'] = [
				'class' => WikibaseLexemeEntityFormLibrary::class,
				'deferLoad' => true,
			];
			$extraLibraries['mw.wikibase.lexeme.entity.sense'] = [
				'class' => WikibaseLexemeEntitySenseLibrary::class,
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

	/** @inheritDoc */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addPostDatabaseUpdateMaintenance( FixPagePropsSortkey::class );
	}

}

<?php

namespace Wikibase\Lexeme;

use MediaWiki\MediaWikiServices;
use ResourceLoader;

/**
 * MediaWiki hook handlers for the Wikibase Lexeme extension.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class WikibaseLexemeHooks {

	/**
	 * Hook to register the lexeme and other entity namespaces for EntityNamespaceLookup.
	 *
	 * @param int[] $entityNamespacesSetting
	 */
	public static function onWikibaseEntityNamespaces( array &$entityNamespacesSetting ) {
		// XXX: ExtensionProcessor should define an extra config object for every extension.
		$config = MediaWikiServices::getInstance()->getMainConfig();

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

		// Setting the namespace to false disabled automatic registration.
		$lexemeNamespaceId = $config->get( 'LexemeNamespace' );
		$lexemeNamespaceName = 'Lexeme';
		if ( $lexemeNamespaceId !== false ) {
			$namespaces = self::registerNamespace(
				$namespaces,
				$lexemeNamespaceId,
				$lexemeNamespaceName
			);
		}

		$talkNamespaceId = $config->get( 'LexemeTalkNamespace' );
		$talkNamespaceName = $lexemeNamespaceName . '_Talk';
		if ( $talkNamespaceId !== false ) {
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
	 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
	 * objects or loading classes here!
	 *
	 * @param array[] $entityTypeDefinitions
	 */
	public static function onWikibaseEntityTypes( array &$entityTypeDefinitions ) {
		$entityTypeDefinitions = array_merge(
			$entityTypeDefinitions,
			require __DIR__ . '/../WikibaseLexeme.entitytypes.php'
		);
	}

	/**
	 * Adds the definition of the data types related to lexeme to the definitions array
	 * Wikibase uses.
	 *
	 * @see WikibaseLexeme.datatypes.php
	 *
	 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
	 * objects or loading classes here!
	 *
	 * @param array[] $dataTypeDefinitions
	 */
	public static function onWikibaseDataTypes( array &$dataTypeDefinitions ) {
		$dataTypeDefinitions = array_merge(
			$dataTypeDefinitions,
			require __DIR__ . '/../WikibaseLexeme.datatypes.php'
		);
	}

	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader $rl ) {
		$testModules['qunit']['WikibaseLexeme.tests'] = [
			'scripts' => [
				'tests/qunit/datamodel/LexemeForm.tests.js',
				'tests/qunit/datamodel/Sense.tests.js',
				'tests/qunit/jquery.wikibase.lexemeformlistview.tests.js',
				'tests/qunit/jquery.wikibase.lexemeformview.tests.js',
				'tests/qunit/jquery.wikibase.grammaticalfeatureview.tests.js',
				'tests/qunit/serialization/LexemeDeserializer.tests.js',
				'tests/qunit/services/ItemLookup.tests.js',
				'tests/qunit/services/LanguageFromItemExtractor.tests.js',
				'tests/qunit/special/formHelpers/LexemeLanguageFieldObserver.tests.js',
				'tests/qunit/widgets/ItemSelectorWidget.tests.js',
				'tests/qunit/widgets/GrammaticalFeatureListWidget.tests.js',
				'tests/qunit/widgets/LemmaWidgetStore.tests.js',
				'tests/qunit/widgets/LemmaWidget.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.lexemeformlistview',
				'jquery.wikibase.lexemeformview',
				'jquery.wikibase.grammaticalfeatureview',
				'oojs-ui',
				'wikibase.lexeme.datamodel.LexemeForm',
				'wikibase.lexeme.datamodel.Sense',
				'wikibase.lexeme.serialization.LexemeDeserializer',
				'wikibase.lexeme.services.ItemLookup',
				'wikibase.lexeme.services.LanguageFromItemExtractor',
				'wikibase.lexeme.special.formHelpers.LexemeLanguageFieldObserver',
				'wikibase.lexeme.widgets.ItemSelectorWidget',
				'wikibase.lexeme.widgets.GrammaticalFeatureListWidget',
				'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidgetStore',
				'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget',
				'vue',
				'vuex',
			],
			'localBasePath' => dirname( __DIR__ ),
			'remoteExtPath' => 'WikibaseLexeme',
		];

		return true;
	}

	/**
	 * @param array $namespaces
	 * @param int $namespaceId
	 * @param string $namespaceName
	 * @return array
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

		if ( !isset( $namespaces[$namespaceId] ) && $namespaceId >= 100 ) {
			$namespaces[$namespaceId] = $namespaceName;
		}

		return $namespaces;
	}

}

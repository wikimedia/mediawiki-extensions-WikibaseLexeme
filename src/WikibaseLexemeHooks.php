<?php

namespace Wikibase\Lexeme;

use IContextSource;
use MediaWiki\MediaWikiServices;
use PageProps;
use ResourceLoader;
use Wikibase\Lexeme\MediaWiki\Actions\InfoActionHookHandler;
use Wikibase\Lexeme\MediaWiki\ParserOutput\LexemeParserOutputUpdater;
use Wikibase\Repo\ParserOutput\CompositeStatementDataUpdater;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\WikibaseSettings;

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
		$lexemeNamespaceName = 'Lexeme';
		if ( $lexemeNamespaceId !== false ) {
			$namespaces = self::registerNamespace(
				$namespaces,
				$lexemeNamespaceId,
				$lexemeNamespaceName
			);
		}

		$talkNamespaceId = $config->get( 'LexemeTalkNamespace' );
		$talkNamespaceName = $lexemeNamespaceName . '_talk';
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

		$entityTypeDefinitions = array_merge_recursive(
			$entityTypeDefinitions,
			require __DIR__ . '/../WikibaseLexeme.entitytypes.php',
			require __DIR__ . '/../WikibaseLexeme.entitytypes.repo.php'
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

	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader $rl ) {
		$moduleBase = [
			'localBasePath' => dirname( __DIR__ ),
			'remoteExtPath' => 'WikibaseLexeme',
		];

		$testModules['qunit'] += [
			'WikibaseLexeme.tests' => $moduleBase + [
				'scripts' => [
					'tests/qunit/datamodel/Form.tests.js',
					'tests/qunit/datamodel/Sense.tests.js',
					'tests/qunit/experts/Lexeme.tests.js',
					'tests/qunit/experts/Form.tests.js',
					'tests/qunit/jquery.wikibase.lexemeformlistview.tests.js',
					'tests/qunit/jquery.wikibase.lexemeformview.tests.js',
					'tests/qunit/jquery.wikibase.grammaticalfeatureview.tests.js',
					'tests/qunit/jquery.wikibase.senselistview.tests.js',
					'tests/qunit/jquery.wikibase.senseview.tests.js',
					'tests/qunit/widgets/ItemSelectorWidget.tests.js',
					'tests/qunit/widgets/GrammaticalFeatureListWidget.tests.js',
				],
				'dependencies' => [
					'jquery.valueview.tests.testExpert',
					'jquery.wikibase.lexemeview',
					'oojs-ui',
					'wikibase.experts.Lexeme',
					'wikibase.experts.Form',
					'wikibase.lexeme.datamodel.Form',
					'wikibase.lexeme.datamodel.Sense',
					'wikibase.lexeme.serialization.LexemeDeserializer',
					'wikibase.lexeme.widgets.ItemSelectorWidget',
					'wikibase.lexeme.widgets.GrammaticalFeatureListWidget',
					'wikibase.lexeme.widgets.RepresentationWidget',
					'wikibase.lexeme.view.ViewFactoryFactory',
					'wikibase.tests.qunit.testrunner',
					'wikibase.view.tests.getMockListItemAdapter',
					'vue2',
					'vuex',
				],
			],
			'WikibaseLexeme.tests.ItemLookup' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/services/ItemLookup.tests.js',
					'resources/services/ItemLookup.js',
				],
			],
			'WikibaseLexeme.tests.LanguageFromItemExtractor' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/services/LanguageFromItemExtractor.tests.js',
					'resources/services/LanguageFromItemExtractor.js',
				],
			],
			'WikibaseLexeme.tests.LexemeLanguageFieldObserver' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/special/formHelpers/LexemeLanguageFieldObserver.tests.js',
					'resources/special/formHelpers/LexemeLanguageFieldObserver.js',
				],
			],
			'WikibaseLexeme.tests.FormChanger' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/entityChangers/FormChanger.tests.js',
					'resources/entityChangers/FormChanger.js',
					'resources/serialization/FormSerializer.js',
				],
				'dependencies' => [
					"wikibase.lexeme.view.ViewFactoryFactory"
				]
			],
			'WikibaseLexeme.tests.SenseChanger' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/entityChangers/SenseChanger.tests.js',
					'resources/entityChangers/SenseChanger.js',
					'resources/serialization/SenseSerializer.js',
				],
				'dependencies' => [
					"wikibase.lexeme.view.ViewFactoryFactory"
				]
			],
			'WikibaseLexeme.tests.LexemeRevisionStore' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/entityChangers/LexemeRevisionStore.tests.js',
					'resources/entityChangers/LexemeRevisionStore.js',
				],
				'dependencies' => [
					"wikibase.lexeme.view.ViewFactoryFactory"
				]
			],
			'WikibaseLexeme.tests.LexemeDeserializer' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/serialization/LexemeDeserializer.tests.js',
					'resources/serialization/LexemeDeserializer.js',
					'resources/datamodel/Lexeme.js',
				],
				'dependencies' => [
					"wikibase.lexeme.serialization.LexemeDeserializer"
				]
			],
			'WikibaseLexeme.tests.datamodel.Lexeme' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/datamodel/Lexeme.tests.js',
					'resources/datamodel/Lexeme.js',
				],
				'dependencies' => [
					"wikibase.lexeme.serialization.LexemeDeserializer"
				]
			],
			'WikibaseLexeme.tests.serialization.FormSerializer' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/serialization/FormSerializer.tests.js',
					'resources/serialization/FormSerializer.js',
				],
				'dependencies' => [
					"wikibase.lexeme.view.ViewFactoryFactory"
				]
			],
			'WikibaseLexeme.tests.serialization.SenseSerializer' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/serialization/SenseSerializer.tests.js',
					'resources/serialization/SenseSerializer.js',
				],
				'dependencies' => [
					"wikibase.lexeme.view.ViewFactoryFactory"
				]
			],
			'WikibaseLexeme.tests.ViewFactoryFactory' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/view/ViewFactoryFactory.tests.js',
					'resources/view/ViewFactoryFactory.js',
					'resources/view/ReadModeViewFactory.js',
					'resources/view/ControllerViewFactory.js',
					'resources/entityChangers/LexemeRevisionStore.js',
					'resources/entityChangers/FormChanger.js',
					'resources/serialization/FormSerializer.js',
					'resources/entityChangers/SenseChanger.js',
					'resources/serialization/SenseSerializer.js',
				],
				'dependencies' => [
					"wikibase.lexeme.view.ViewFactoryFactory"
				]
			],
			'WikibaseLexeme.tests.ControllerViewFactory' => $moduleBase + [
				'packageFiles' => [
					'tests/qunit/view/ControllerViewFactory.tests.js',
					'resources/view/ControllerViewFactory.js',
					'resources/entityChangers/LexemeRevisionStore.js',
					'resources/entityChangers/FormChanger.js',
					'resources/serialization/FormSerializer.js',
					'resources/entityChangers/SenseChanger.js',
					'resources/serialization/SenseSerializer.js',
				],
				'dependencies' => [
					"wikibase.lexeme.view.ViewFactoryFactory"
				]
			],
		];

		return true;
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

		if ( !isset( $namespaces[$namespaceId] ) && $namespaceId >= 100 ) {
			$namespaces[$namespaceId] = $namespaceName;
		}

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
		$config = MediaWikiServices::getInstance()->getMainConfig();
		if ( !$config->get( 'LexemeEnableRepo' ) ) {
			return;
		}

		$namespaceChecker = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$infoActionHookHandler = new InfoActionHookHandler(
			$namespaceChecker,
			WikibaseRepo::getDefaultInstance()->getEntityIdLookup(),
			PageProps::getInstance(),
			$context
		);

		$pageInfo = $infoActionHookHandler->handle( $context, $pageInfo );
	}

}

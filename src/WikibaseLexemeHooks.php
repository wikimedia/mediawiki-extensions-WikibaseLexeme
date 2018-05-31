<?php

namespace Wikibase\Lexeme;

use CirrusSearch\Profile\ConfigProfileRepository;
use CirrusSearch\Profile\SearchProfileService;
use MediaWiki\MediaWikiServices;
use ResourceLoader;
use Wikibase\Lexeme\Search\LexemeSearchEntity;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;
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
		// Do not register lexeme namespaces when the repo is not enabled.
		if ( !WikibaseSettings::isRepoEnabled() ) {
			return;
		}

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
				'tests/qunit/datamodel/Form.tests.js',
				'tests/qunit/datamodel/Sense.tests.js',
				'tests/qunit/entityChangers/FormChanger.tests.js',
				'tests/qunit/entityChangers/LexemeRevisionStore.tests.js',
				'tests/qunit/experts/Lexeme.tests.js',
				'tests/qunit/experts/Form.tests.js',
				'tests/qunit/jquery.wikibase.lexemeformlistview.tests.js',
				'tests/qunit/jquery.wikibase.lexemeformview.tests.js',
				'tests/qunit/jquery.wikibase.grammaticalfeatureview.tests.js',
				'tests/qunit/jquery.wikibase.senselistview.tests.js',
				'tests/qunit/jquery.wikibase.senseview.tests.js',
				'tests/qunit/serialization/LexemeDeserializer.tests.js',
				'tests/qunit/serialization/FormSerializer.tests.js',
				'tests/qunit/services/ItemLookup.tests.js',
				'tests/qunit/services/LanguageFromItemExtractor.tests.js',
				'tests/qunit/special/formHelpers/LexemeLanguageFieldObserver.tests.js',
				'tests/qunit/widgets/ItemSelectorWidget.tests.js',
				'tests/qunit/widgets/GrammaticalFeatureListWidget.tests.js',
				'tests/qunit/view/ViewFactoryFactory.tests.js',
				'tests/qunit/i18n/Messages.tests.js'
			],
			'dependencies' => [
				'jquery.valueview.tests.testExpert',
				'jquery.wikibase.lexemeformlistview',
				'jquery.wikibase.lexemeformview',
				'jquery.wikibase.grammaticalfeatureview',
				'jquery.wikibase.senselistview',
				'jquery.wikibase.senseview',
				'oojs-ui',
				'wikibase.experts.Lexeme',
				'wikibase.experts.Form',
				'wikibase.lexeme.datamodel.Form',
				'wikibase.lexeme.datamodel.Sense',
				'wikibase.lexeme.entityChangers.FormChanger',
				'wikibase.lexeme.entityChangers.LexemeRevisionStore',
				'wikibase.lexeme.serialization.LexemeDeserializer',
				'wikibase.lexeme.serialization.FormSerializer',
				'wikibase.lexeme.services.ItemLookup',
				'wikibase.lexeme.services.LanguageFromItemExtractor',
				'wikibase.lexeme.special.formHelpers.LexemeLanguageFieldObserver',
				'wikibase.lexeme.widgets.ItemSelectorWidget',
				'wikibase.lexeme.widgets.GrammaticalFeatureListWidget',
				'wikibase.lexeme.widgets.GlossWidget',
				'wikibase.lexeme.widgets.RepresentationWidget',
				'wikibase.lexeme.i18n.Messages',
				'wikibase.lexeme.view.ViewFactoryFactory',
				'wikibase.tests.qunit.testrunner',
				'vue',
				'vuex',
			],
			'localBasePath' => dirname( __DIR__ ),
			'remoteExtPath' => 'WikibaseLexeme',
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

	/**
	 * Register our cirrus profiles.
	 *
	 * @param SearchProfileService $service
	 */
	public static function onCirrusSearchProfileService( SearchProfileService $service ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		// register base profiles available on all wikibase installs
		$service->registerFileRepository( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			'lexeme_base', __DIR__ . '/Search/LexemePrefixSearchProfiles.php' );
		$service->registerFileRepository( SearchProfileService::RESCORE_FUNCTION_CHAINS,
			'lexeme_base', __DIR__ . '/Search/LexemeRescoreFunctions.php' );
		$service->registerFileRepository( SearchProfileService::RESCORE,
			'lexeme_base', __DIR__ . '/Search/LexemeRescoreProfiles.php' );

		// register custom profiles provided in the WikibaseLexeme config settings
		$service->registerRepository(
			new ConfigProfileRepository( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
				'lexeme_config', 'LexemePrefixSearchProfiles', $config )
		);
		// Rescore functions for lexemes
		$service->registerRepository(
			new ConfigProfileRepository( SearchProfileService::RESCORE_FUNCTION_CHAINS,
				'lexeme_config', 'LexemeRescoreFunctions', $config )
		);

		// Determine the default rescore profile to use for entity autocomplete search
		$service->registerDefaultProfile( SearchProfileService::RESCORE,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, EntitySearchElastic::DEFAULT_RESCORE_PROFILE );
		$service->registerConfigOverride( SearchProfileService::RESCORE,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, $config, 'LexemePrefixRescoreProfile' );
		// add the possibility to override the profile by setting the URI param cirrusRescoreProfile
		$service->registerUriParamOverride( SearchProfileService::RESCORE,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, 'cirrusRescoreProfile' );

		// Determine the default query builder profile to use for entity autocomplete search
		$service->registerDefaultProfile( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, EntitySearchElastic::DEFAULT_QUERY_BUILDER_PROFILE );
		$service->registerConfigOverride( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, $config, 'LexemePrefixSearchProfile' );
		$service->registerUriParamOverride( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, 'cirrusWBProfile' );
	}

}

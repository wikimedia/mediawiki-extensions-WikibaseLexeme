<?php

namespace Wikibase\Lexeme;

use CirrusSearch\Profile\ConfigProfileRepository;
use CirrusSearch\Profile\SearchProfileService;
use MediaWiki\MediaWikiServices;
use ResourceLoader;
use SearchResult;
use SpecialSearch;
use Wikibase\Lexeme\DataAccess\Search\LexemeFullTextQueryBuilder;
use Wikibase\Lexeme\DataAccess\Search\LexemeResult;
use Wikibase\Lexeme\DataAccess\Search\LexemeSearchEntity;
use Wikibase\Lexeme\MediaWiki\ParserOutput\LexemeParserOutputUpdater;
use Wikibase\Repo\ParserOutput\CompositeStatementDataUpdater;
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
		$testModules['qunit']['WikibaseLexeme.tests'] = [
			'scripts' => [
				'tests/qunit/datamodel/Form.tests.js',
				'tests/qunit/datamodel/Sense.tests.js',
				'tests/qunit/datamodel/Lexeme.tests.js',
				'tests/qunit/entityChangers/FormChanger.tests.js',
				'tests/qunit/entityChangers/SenseChanger.tests.js',
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
				'tests/qunit/serialization/SenseSerializer.tests.js',
				'tests/qunit/services/ItemLookup.tests.js',
				'tests/qunit/services/LanguageFromItemExtractor.tests.js',
				'tests/qunit/special/formHelpers/LexemeLanguageFieldObserver.tests.js',
				'tests/qunit/widgets/ItemSelectorWidget.tests.js',
				'tests/qunit/widgets/LabelDescriptionOptionWidget.tests.js',
				'tests/qunit/widgets/GrammaticalFeatureListWidget.tests.js',
				'tests/qunit/view/ViewFactoryFactory.tests.js',
				'tests/qunit/view/ControllerViewFactory.tests.js',
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
				'wikibase.lexeme.entityChangers.SenseChanger',
				'wikibase.lexeme.entityChangers.LexemeRevisionStore',
				'wikibase.lexeme.serialization.LexemeDeserializer',
				'wikibase.lexeme.serialization.FormSerializer',
				'wikibase.lexeme.serialization.SenseSerializer',
				'wikibase.lexeme.services.ItemLookup',
				'wikibase.lexeme.services.LanguageFromItemExtractor',
				'wikibase.lexeme.special.formHelpers.LexemeLanguageFieldObserver',
				'wikibase.lexeme.widgets.ItemSelectorWidget',
				'wikibase.lexeme.widgets.LabelDescriptionOptionWidget',
				'wikibase.lexeme.widgets.GrammaticalFeatureListWidget',
				'wikibase.lexeme.widgets.GlossWidget',
				'wikibase.lexeme.widgets.RepresentationWidget',
				'wikibase.lexeme.view.ControllerViewFactory',
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

		// Do not add Lexeme specific search stuff if we are not a repo
		if ( !WikibaseSettings::isRepoEnabled() || !$config->get( 'LexemeEnableRepo' ) ||
			$config->get( 'LexemeDisableCirrus' ) ) {
			return;
		}

		// register base profiles available on all wikibase installs
		$service->registerFileRepository( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			'lexeme_base', __DIR__ . '/DataAccess/Search/LexemePrefixSearchProfiles.php' );
		$service->registerFileRepository( SearchProfileService::RESCORE_FUNCTION_CHAINS,
			'lexeme_base', __DIR__ . '/DataAccess/Search/LexemeRescoreFunctions.php' );
		$service->registerFileRepository( SearchProfileService::RESCORE,
			'lexeme_base', __DIR__ . '/DataAccess/Search/LexemeRescoreProfiles.php' );
		$service->registerFileRepository( SearchProfileService::FT_QUERY_BUILDER,
			'lexeme_base', __DIR__ . '/DataAccess/Search/LexemeSearchProfiles.php' );

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
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX,
			EntitySearchElastic::DEFAULT_RESCORE_PROFILE );
		$service->registerConfigOverride( SearchProfileService::RESCORE,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, $config, 'LexemePrefixRescoreProfile' );
		// add the possibility to override the profile by setting the URI param cirrusRescoreProfile
		$service->registerUriParamOverride( SearchProfileService::RESCORE,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, 'cirrusRescoreProfile' );

		// Determine the default query builder profile to use for entity autocomplete search
		$service->registerDefaultProfile( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX,
			EntitySearchElastic::DEFAULT_QUERY_BUILDER_PROFILE );
		$service->registerConfigOverride( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, $config, 'LexemePrefixSearchProfile' );
		$service->registerUriParamOverride( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			LexemeSearchEntity::CONTEXT_LEXEME_PREFIX, 'cirrusWBProfile' );

		// Determine query builder profile for fulltext search
		$service->registerDefaultProfile( SearchProfileService::FT_QUERY_BUILDER,
			LexemeFullTextQueryBuilder::CONTEXT_LEXEME_FULLTEXT,
			LexemeFullTextQueryBuilder::LEXEME_DEFAULT_PROFILE );
		$service->registerUriParamOverride( SearchProfileService::FT_QUERY_BUILDER,
			LexemeFullTextQueryBuilder::CONTEXT_LEXEME_FULLTEXT, 'cirrusWBProfile' );

		// Determine the default rescore profile to use for fulltext search
		$service->registerDefaultProfile( SearchProfileService::RESCORE,
			LexemeFullTextQueryBuilder::CONTEXT_LEXEME_FULLTEXT,
			LexemeFullTextQueryBuilder::LEXEME_DEFAULT_PROFILE );
		$service->registerConfigOverride( SearchProfileService::RESCORE,
			LexemeFullTextQueryBuilder::CONTEXT_LEXEME_FULLTEXT, $config,
			'LexemeFulltextRescoreProfile' );
		// add the possibility to override the profile by setting the URI param cirrusRescoreProfile
		$service->registerUriParamOverride( SearchProfileService::RESCORE,
			LexemeFullTextQueryBuilder::CONTEXT_LEXEME_FULLTEXT, 'cirrusRescoreProfile' );
	}

	/**
	 * @param SpecialSearch $searchPage
	 * @param SearchResult $result
	 * @param array $terms
	 * @param $link
	 * @param $redirect
	 * @param $section
	 * @param $extract
	 * @param $score
	 * @param $size
	 * @param $date
	 * @param $related
	 * @param $html
	 */
	public static function onShowSearchHit( SpecialSearch $searchPage, SearchResult $result,
		array $terms, &$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related,
		&$html
	) {

		if ( !( $result instanceof LexemeResult ) ) {
			return;
		}

		// set $size to size metrics
		$size = $searchPage->msg(
			'wikibaselexeme-search-result-stats',
			$result->getStatementCount(),
			$result->getFormCount()
		)->escaped();
	}

	public static function onParserOutputUpdaterConstruction(
		CompositeStatementDataUpdater $statementUpdater, array &$entityUpdaters
	) {
		$entityUpdaters[] = new LexemeParserOutputUpdater( $statementUpdater );
	}

}

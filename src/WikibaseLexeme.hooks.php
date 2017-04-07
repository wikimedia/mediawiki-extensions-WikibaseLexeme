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
	 * Hook to register the default namespace names with $wgExtraNamespaces.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SetupAfterCache
	 */
	public static function onSetupAfterCache() {
		global $wgExtraNamespaces;

		// XXX: ExtensionProcessor should define an extra config object for every extension.
		$config = MediaWikiServices::getInstance()->getMainConfig();

		# Sense and Form will be added later.
		$entities = [ 'Lexeme' ];
		foreach ( $entities as $entity ) {
			// Setting the namespace to false disabled automatic registration.
			$entityNamespace = $config->get( $entity . 'Namespace' );
			$talkNamespace = $config->get( $entity . 'TalkNamespace' );

			if ( $entityNamespace !== false ) {
				if ( !isset( $wgExtraNamespaces[$entityNamespace] ) && $entityNamespace >= 100 ) {
					$wgExtraNamespaces[$entityNamespace] = $entity;
				}
			}

			if ( $talkNamespace !== false ) {
				if ( !isset( $wgExtraNamespaces[$talkNamespace] ) && $entityNamespace >= 100 ) {
					// XXX: Localize the default talk namespace?
					$wgExtraNamespaces[$talkNamespace] = $entity . '_Talk';
				}
			}
		}
		// FIXME: Ugly hack find solution to avoid this line
		\MWNamespace::getCanonicalNamespaces( true );

		return true;
	}

	/**
	 * Adds the definition of the media info entity type to the definitions array Wikibase uses.
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

	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader $rl ) {
		$testModules['qunit']['WikibaseLexeme.tests'] = [
			'scripts' => [
				'tests/qunit/serialization/LexemeDeserializer.tests.js',
				'tests/qunit/widgets/LanguageLookupWidget.tests.js',
				'tests/qunit/services/ItemLookup.tests.js',
				'tests/qunit/services/LanguageFromItemExtractor.tests.js',
				'tests/qunit/special/formHelpers/LexemeLanguageFieldObserver.tests.js',
			],
			'dependencies' => [
				'wikibase.lexeme.serialization.LexemeDeserializer',
				'wikibase.lexeme.widgets.LanguageLookupWidget',
				'wikibase.lexeme.services.ItemLookup',
				'wikibase.lexeme.services.LanguageFromItemExtractor',
				'wikibase.lexeme.special.formHelpers.LexemeLanguageFieldObserver',
				'oojs-ui'
			],
			'localBasePath' => dirname( __DIR__ ),
			'remoteExtPath' => 'WikibaseLexeme',
		];

		return true;
	}

}

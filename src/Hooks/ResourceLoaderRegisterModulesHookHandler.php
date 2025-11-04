<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Hooks;

use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class ResourceLoaderRegisterModulesHookHandler implements ResourceLoaderRegisterModulesHook {

	private ?SettingsArray $settings;

	public function __construct( ?SettingsArray $settings ) {
		$this->settings = $settings;
	}

	public function onResourceLoaderRegisterModules( ResourceLoader $rl ): void {
		if ( !$this->settings ) {
			return;
		}
		$moduleTemplate = [
			'localBasePath' => __DIR__ . '/../..',
			'remoteExtPath' => 'WikibaseLexeme',
		];

		// temporarily register this RL module only if the feature flag for mobile editing or its beta feature are
		// enabled, so that wikis without either feature flag don't even pay the small cost of loading the module
		// *definition* (when the feature stabilizes, this should move into repo/resources/Resources.php: T395783)
		if (
			$this->settings->getSetting( 'tmpMobileEditingUI' ) ||
			$this->settings->getSetting( 'tmpEnableMobileEditingUIBetaFeature' )
		) {
			$modules = [ 'wikibaseLexeme.wbui2025.entityViewInit' => $moduleTemplate +
				[
					'packageFiles' => [
						'resources/view/wikibase.wbui2025/wikibaseLexeme.wbui2025.entityViewInit.js',
					],
					'dependencies' => [
						'wikibase.wbui2025.lib',
					],
				],
			];
			$rl->register( $modules );
		}
	}
}

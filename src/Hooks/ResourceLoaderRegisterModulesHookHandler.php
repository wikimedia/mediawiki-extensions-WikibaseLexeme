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

	public function __construct(
		private readonly ?SettingsArray $settings,
	) {
	}

	public function onResourceLoaderRegisterModules( ResourceLoader $rl ): void {
		$moduleTemplate = [
			'localBasePath' => __DIR__ . '/../..',
			'remoteExtPath' => 'WikibaseLexeme',
		];

		// Temporarily register this RL module only if the feature flag for mobile editing is
		// enabled, so that wikis without either feature flag don't even pay the small cost of loading the module
		// *definition* (when the feature stabilizes, this should move into repo/resources/Resources.php: T395783)
		if ( $this->settings?->getSetting( 'tmpMobileEditingUI' ) ) {
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

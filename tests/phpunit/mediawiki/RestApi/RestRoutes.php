<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\RestApi;

/**
 * @license GPL-2.0-or-later
 */
class RestRoutes {

	public static function getAllRouteDefinitions(): array {
		$extensionRoot = __DIR__ . '/../../../..';

		return array_merge(
			json_decode( file_get_contents( "$extensionRoot/extension.json" ), true )['RestRoutes'] ?? [],
			json_decode( file_get_contents( "$extensionRoot/src/MediaWiki/RestApi/routes.dev.json" ), true ),
		);
	}

}
